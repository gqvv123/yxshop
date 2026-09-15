// pages/order/confirm/confirm.js
const api = require('../../../api/index');
const auth = require('../../../utils/auth');
const config = require('../../../config/index');
const { fmtPrice } = require('../../../utils/format');

Page({
  data: {
    statusBarHeight: 20,
    items: [],     // 入参 items
    goods: [],     // 后端商品详情后的展示数据
    address: null,
    totalPrice: '0.00',
    totalQty: 0,
    paymentMethod: 'wechat',
    remark: '',
    submitting: false,
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
  },

  onShow() {
    // 从地址列表选择回来
    const picked = wx.getStorageSync('yxshop:pickedAddress');
    if (picked) {
      this.setData({ address: picked });
      wx.removeStorageSync('yxshop:pickedAddress');
    }
  },

  async loadDefaultAddress() {
    try {
      const list = await api.address.getList();
      const arr = Array.isArray(list) ? list : (list && list.data) || [];
      const def = arr.find((x) => x.is_default == 1) || arr[0] || null;
      this.setData({ address: def });
    } catch (e) { /* ignore */ }
  },

  /**
   * 拉商品详情用于预览。后端 createOrder 才是价格真源，这里仅展示。
   * 并行请求所有商品详情，避免串行 N+1 延迟。
   */
  async loadGoodsPreview() {
    let total = 0;
    let qty = 0;
    const tasks = this.data.items.map((it) =>
      api.item.getDetail(it.item_id)
        .then((detail) => {
          if (!detail) return null;
          const price = Number(detail.price || detail.sale_price || 0);
          total += price * Number(it.quantity || 1);
          qty += Number(it.quantity || 1);
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
      const goods = results.filter(Boolean);
      this.setData({
        goods,
        totalPrice: fmtPrice(total),
        totalQty: qty,
      });
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

  requestWechatPay(p) {
    return new Promise((resolve, reject) => {
      wx.requestPayment({
        timeStamp: p.timeStamp,
        nonceStr: p.nonceStr,
        package: p.package,
        signType: p.signType || 'MD5',
        paySign: p.paySign,
        success: () => { wx.showToast({ title: '支付成功', icon: 'success' }); resolve(); },
        fail: (err) => {
          wx.showToast({ title: err && err.errMsg.includes('cancel') ? '已取消支付' : '支付失败', icon: 'none' });
          reject(err);
        },
      });
    });
  },
});
