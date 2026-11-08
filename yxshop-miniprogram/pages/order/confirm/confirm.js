// pages/order/confirm/confirm.js
const api = require('../../../api/index');
const auth = require('../../../utils/auth');
const config = require('../../../config/index');
const { fmtPrice } = require('../../../utils/format');
const { callWxPay } = require('../../../utils/pay');

Page({
  data: {
    statusBarHeight: 20,
    items: [],     // 入参 items
    goods: [],     // 后端商品详情后的展示数据
    address: null,
    paymentMethod: 'wechat',
    remark: '',
    submitting: false,

    // ===== 金额明细（唯一真源：服务端 /order/preview）=====
    goodsAmount: '0.00',
    memberDiscount: '0.00',
    couponDiscount: '0.00',
    shippingFee: '0.00',
    freightFree: false,
    payTotal: '0.00',
    totalQty: 0,

    // ===== 会员等级（等级折扣展示）=====
    member: null,

    // ===== 优惠券 =====
    coupons: [],
    couponId: 0,
    couponName: '',
    couponError: '',
    showCouponSheet: false,

    // 配送方式（对应管理端「物流配送 → 配送模板」）
    deliveries: [],       // 可选配送方式
    deliveryId: 0,        // 选中的配送方式 id
    deliveryName: '',     // 选中的配送方式名称

    regionId: 0,          // 收货地区 id（用于运费规则匹配）
    previewing: false,
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    if (!auth.isLoggedIn()) {
      wx.redirectTo({ url: '/pages/login/login' });
      return;
    }
    const items = wx.getStorageSync('yxshop:checkoutItems') || [];
    if (!items.length) {
      wx.showToast({ title: '没有要结算的商品', icon: 'none' });
      return setTimeout(() => wx.navigateBack(), 800);
    }
    this.setData({ items });
    this.loadDefaultAddress();
    this.loadGoodsPreview();
    this.loadDeliveries();
  },

  /**
   * 拉取可选配送方式（对应管理端「物流配送 → 配送模板」）
   */
  async loadDeliveries() {
    try {
      const res = await api.delivery.getList({ app_id: config.appId });
      const list = Array.isArray(res) ? res : (res && Array.isArray(res.list) ? res.list : []);
      const deliveries = list
        // 空名模板是无效配置（历史上批量测试留下的），显示成「默认配送」会造成一串同名选项
        .filter((d) => (d.status === undefined || Number(d.status) === 1) && (d.name || '').trim() !== '')
        .map((d) => ({
          id: d.id,
          name: d.name.trim(),
          method: d.method,
          sort: Number(d.sort || 0),
        }))
        .sort((a, b) => a.sort - b.sort);
      if (!deliveries.length) {
        this.setData({ deliveries: [], deliveryId: 0 });
        return;
      }
      // 默认选第一个
      this.setData({ deliveries, deliveryId: deliveries[0].id, deliveryName: deliveries[0].name });
      this.refreshPreview();
    } catch (e) {
      // 配送方式拉取失败不阻断下单
      this.setData({ deliveries: [] });
    }
  },

  /**
   * 结算试算：金额、会员折扣、优惠券折扣、运费、可用券全部以服务端为准
   *
   * 为什么不只在前端算：券门槛、会员折率、运费规则都在服务端，
   * 前端自己算必然和最终下单金额对不上。
   */
  async refreshPreview() {
    if (!this.data.items.length) return;
    this.setData({ previewing: true });

    const payload = {
      items: this.data.items.map((it) => ({
        item_id: it.item_id,
        quantity: it.quantity,
        spec_id: it.spec_id || 0,
      })),
      coupon_id: this.data.couponId || 0,
      address_id: this.data.address ? (this.data.address.id || this.data.address.address_id || 0) : 0,
      delivery_id: this.data.deliveryId || 0,
      app_id: config.appId,
    };

    try {
      const d = (await api.order.preview(payload)) || {};

      // 券失效（后端回传原因）时清空选中，让用户重新选
      if (d.coupon_error) {
        this.setData({ couponId: 0, couponError: d.coupon_error });
        wx.showToast({ title: d.coupon_error, icon: 'none' });
      }

      const coupons = (d.available_coupons || []).map((c) => ({
        coupon_id: c.coupon_id,
        name: c.name,
        amountText: fmtPrice(c.discount_amount),
        minText: fmtPrice(c.min_amount),
        hasThreshold: Number(c.min_amount) > 0,
      }));

      const selected = coupons.find((c) => String(c.coupon_id) === String(d.selected_coupon_id));

      this.setData({
        goodsAmount: fmtPrice(d.goods_amount),
        memberDiscount: fmtPrice(d.member_discount),
        couponDiscount: fmtPrice(d.coupon_discount),
        shippingFee: fmtPrice(d.shipping_fee),
        freightFree: Number(d.shipping_fee) <= 0,
        payTotal: fmtPrice(d.pay_amount),
        totalQty: d.total_quantity || 0,
        member: d.member || null,
        coupons,
        couponName: selected ? selected.name : '',
        couponError: d.coupon_error || '',
      });

      // 服务端可用券里已经没有当前选中的券 -> 说明券被用掉/失效
      if (this.data.couponId && !selected && !d.coupon_error) {
        this.setData({ couponId: 0 });
      }
    } catch (e) {
      // 试算失败不阻断下单，回落到本地估算；提交时仍以服务端为准
      this.fallbackAmount();
    } finally {
      this.setData({ previewing: false });
    }
  },

  /** 试算不可用时的本地兜底估算（仅影响展示） */
  fallbackAmount() {
    const goods = this.data.goods || [];
    let total = 0;
    let qty = 0;
    goods.forEach((g) => {
      const price = Number(g.price || g.sale_price || 0);
      const n = Number(g.quantity || 1);
      total += price * n;
      qty += n;
    });
    this.setData({
      goodsAmount: fmtPrice(total),
      shippingFee: '0.00',
      freightFree: true,
      memberDiscount: '0.00',
      couponDiscount: '0.00',
      payTotal: fmtPrice(total),
      totalQty: qty,
    });
  },

  onDeliveryChange(e) {
    const id = e.currentTarget.dataset.id;
    const item = this.data.deliveries.find((d) => String(d.id) === String(id));
    this.setData({
      deliveryId: id,
      deliveryName: item ? item.name : '',
    });
    this.refreshPreview();
  },

  /** ===== 优惠券选择 ===== */
  onOpenCouponSheet() {
    if (!this.data.coupons.length) {
      wx.showToast({ title: '暂无可用优惠券', icon: 'none' });
      return;
    }
    this.setData({ showCouponSheet: true });
  },

  onCloseCouponSheet() {
    this.setData({ showCouponSheet: false });
  },

  onSelectCoupon(e) {
    const id = Number(e.currentTarget.dataset.id || 0);
    this.setData({ showCouponSheet: false, couponId: id });
    this.refreshPreview();
  },

  onClearCoupon() {
    this.setData({ showCouponSheet: false, couponId: 0, couponName: '' });
    this.refreshPreview();
  },

  /** 阻止弹层内部点击穿透关闭 */
  noop() {},

  onShow() {
    // 从地址列表选择回来
    const picked = wx.getStorageSync('yxshop:pickedAddress');
    if (picked) {
      this.setData({
        address: picked,
        regionId: Number(picked.district_id || picked.region_id || 0),
      });
      wx.removeStorageSync('yxshop:pickedAddress');
      this.refreshPreview();
    }
  },

  async loadDefaultAddress() {
    try {
      const list = await api.address.getList();
      const arr = Array.isArray(list) ? list : (list && list.data) || [];
      const def = arr.find((x) => x.is_default == 1) || arr[0] || null;
      this.setData({
        address: def,
        regionId: def ? Number(def.district_id || def.region_id || 0) : 0,
      });
      if (def) this.refreshPreview();
    } catch (e) { /* ignore */ }
  },

  /**
   * 拉商品详情用于展示（封面 / 规格名）。价格真源以服务端试算为准。
   * 并行请求所有商品详情，避免串行 N+1 延迟。
   */
  async loadGoodsPreview() {
    const tasks = this.data.items.map((it) =>
      api.item.getDetail(it.item_id)
        .then((detail) => {
          if (!detail) return null;
          const price = Number(detail.price || detail.sale_price || 0);
          return {
            ...detail,
            quantity: it.quantity,
            spec_key_name: it.spec_key_name,
            line_price: fmtPrice(price * Number(it.quantity || 1)),
          };
        })
        .catch(() => null)
    );
    try {
      const results = await Promise.all(tasks);
      this.setData({ goods: results.filter(Boolean) });
      this.refreshPreview();
    } catch (e) {
      this.setData({ goods: [] });
    }
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/cart/cart' });
    }
  },

  onPickAddress() {
    wx.navigateTo({ url: '/pages/address/list/list?picker=1' });
  },

  onPaymentChange(e) {
    this.setData({ paymentMethod: e.detail.value });
  },

  onRemarkInput(e) {
    this.setData({ remark: e.detail.value });
  },

  async onSubmit() {
    if (this.data.submitting) return;
    if (!this.data.address) {
      return wx.showToast({ title: '请选择收货地址', icon: 'none' });
    }
    if (!this.data.items.length) return;

    this.setData({ submitting: true });
    let orderId = null;
    try {
      const order = await api.order.create({
        address_id: this.data.address.id || this.data.address.address_id,
        app_id: config.appId,
        items: this.data.items.map((it) => ({
          item_id: it.item_id,
          quantity: it.quantity,
          spec_id: it.spec_id || 0,
          spec_key: it.spec_key,
          spec_key_name: it.spec_key_name,
        })),
        delivery_id: this.data.deliveryId || 0,
        coupon_id: this.data.couponId || 0,
        remark: this.data.remark,
      });

      orderId = order.order_id || order.id;

      // 创建支付
      const pay = await api.payment.create({
        order_id: orderId,
        payment_method: this.data.paymentMethod,
      });

      // 微信支付
      if (this.data.paymentMethod === 'wechat' && pay && pay.timeStamp && pay.nonceStr) {
        await this.requestWechatPay(pay);
      } else {
        wx.showToast({ title: '已下单，请到订单中支付', icon: 'success' });
      }

      wx.removeStorageSync('yxshop:checkoutItems');
      setTimeout(() => {
        wx.redirectTo({ url: `/pages/order/detail/detail?id=${orderId}` });
      }, 600);
    } catch (e) {
      // 订单已创建但支付环节失败：引导用户去订单页付款
      if (orderId) {
        wx.removeStorageSync('yxshop:checkoutItems');
        wx.showModal({
          title: '提示',
          content: '订单已创建，支付未能完成。是否前往订单页重新支付？',
          confirmText: '去支付',
          success: (res) => {
            if (res.confirm) {
              wx.redirectTo({ url: `/pages/order/detail/detail?id=${orderId}` });
            } else {
              wx.redirectTo({ url: `/pages/order/list/list?status=10` });
            }
          },
        });
      }
    } finally {
      this.setData({ submitting: false });
    }
  },

  async requestWechatPay(p) {
    try {
      await callWxPay(p);
      wx.showToast({ title: '支付成功', icon: 'success' });
    } catch (err) {
      wx.showToast({ title: err && err.errMsg && err.errMsg.includes('cancel') ? '已取消支付' : '支付失败', icon: 'none' });
      throw err;
    }
  },
});
