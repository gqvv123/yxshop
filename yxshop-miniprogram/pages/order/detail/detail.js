// pages/order/detail/detail.js
const api = require('../../../api/index');
const imageUtil = require('../../../utils/image');
const { fmtOrderStatus, fmtPrice, fmtDate, getOrderFlowStatus } = require('../../../utils/format');

/** 状态 -> 图标映射 */
function getStatusIcon(flowStatus) {
  const map = {
    10: 'clock',      // 待付款
    20: 'truck',      // 待发货
    30: 'truck',      // 待收货
    40: 'check-circle', // 已完成
    '-1': 'close',    // 已取消
  };
  return map[flowStatus] || 'package';
}

/** 状态 -> 提示语 */
function getStatusTip(order, flowStatus) {
  switch (String(flowStatus)) {
    case '10':
      return '请尽快完成支付';
    case '20':
      return '商家正在备货中';
    case '30':
      return '商品已发出，请注意查收';
    case '40':
      return order.is_comment ? '订单已完成' : '快去评价吧';
    case '-1':
      return '订单已取消';
    default:
      return '';
  }
}

/** 解析 SKU 标签 */
function parseSkuTags(g) {
  const tags = [];
  if (g.item_attr && typeof g.item_attr === 'string') {
    g.item_attr.split(/[;；,，]/).forEach((s) => {
      const t = s.trim();
      if (t) tags.push(t);
    });
  }
  if (g.spec_key_name && typeof g.spec_key_name === 'string') {
    g.spec_key_name.split(/[;；,，]/).forEach((s) => {
      const t = s.trim();
      if (t && tags.indexOf(t) < 0) tags.push(t);
    });
  }
  return tags;
}

/** 检查 URL 是否为无效图片路径（如 '0' 或以 /0 结尾） */
function isValidImageUrl(url) {
  if (!url) return false;
  var s = String(url).trim();
  if (!s || s === '0') return false;
  if (/\/0$/.test(s) && !/\.(jpg|jpeg|png|gif|webp|bmp|svg)/i.test(s)) return false;
  return true;
}

/** 提取商品图片 URL，带兜底 */
function resolveGoodsImage(g) {
  if (isValidImageUrl(g.image)) return imageUtil.resolve(g.image);
  if (isValidImageUrl(g.goods_image)) return imageUtil.resolve(g.goods_image);
  if (isValidImageUrl(g.cover)) return imageUtil.resolve(g.cover);
  if (isValidImageUrl(g.pic)) return imageUtil.resolve(g.pic);
  if (isValidImageUrl(g.thumb)) return imageUtil.resolve(g.thumb);
  if (Array.isArray(g.images) && g.images.length) {
    var img = g.images[0];
    if (img && isValidImageUrl(img.url)) return imageUtil.resolve(img.url);
    if (img && isValidImageUrl(img.image_id)) return imageUtil.resolve(img.image_id);
    if (img && typeof img === 'string' && isValidImageUrl(img)) return imageUtil.resolve(img);
  }
  if (g.item) {
    if (isValidImageUrl(g.item.main_image)) return imageUtil.resolve(g.item.main_image);
    if (isValidImageUrl(g.item.image)) return imageUtil.resolve(g.item.image);
    if (Array.isArray(g.item.images) && g.item.images.length) {
      var ii = g.item.images[0];
      if (ii && isValidImageUrl(ii.image_id)) return imageUtil.resolve(ii.image_id);
      if (ii && isValidImageUrl(ii.url)) return imageUtil.resolve(ii.url);
    }
  }
  return '';
}

/** 根据订单状态构建操作按钮 */
function buildActions(order, flowStatus) {
  const actions = [];
  const fs = String(flowStatus);

  if (fs === '10') {
    actions.push({ action: 'cancel', label: '取消订单', type: 'default' });
    actions.push({ action: 'pay', label: '立即支付', type: 'primary' });
  } else if (fs === '20') {
    actions.push({ action: 'remind', label: '提醒发货', type: 'default' });
    actions.push({ action: 'refund', label: '申请退款', type: 'default' });
  } else if (fs === '30') {
    actions.push({ action: 'logistics', label: '查看物流', type: 'default' });
    actions.push({ action: 'refund', label: '申请退款', type: 'default' });
    actions.push({ action: 'confirm', label: '确认收货', type: 'primary' });
  } else if (fs === '40') {
    if (!order.is_comment) {
      actions.push({ action: 'review', label: '评价', type: 'primary' });
    }
    actions.push({ action: 'reorder', label: '再次购买', type: 'default' });
    actions.push({ action: 'delete', label: '删除订单', type: 'default' });
  } else if (fs === '-1') {
    actions.push({ action: 'reorder', label: '再次购买', type: 'default' });
    actions.push({ action: 'delete', label: '删除订单', type: 'default' });
  }

  return actions;
}

/** 格式化时间戳 */
function fmtTime(ts) {
  if (!ts || Number(ts) <= 0) return '';
  return fmtDate(Number(ts));
}

Page({
  data: {
    id: '',
    order: null,
    loading: true,
    statusBarHeight: 20,
  },

  onLoad(options) {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    const id = options.id || '';
    this.setData({ id });
    if (id) {
      this.loadDetail();
    } else {
      this.setData({ loading: false });
    }
  },

  async loadDetail() {
    this.setData({ loading: true });
    try {
      const raw = await api.order.getDetail(this.data.id);
      const order = this._decorateOrder(raw);
      this.setData({ order });
    } catch (e) {
      wx.showToast({ title: e.message || '加载失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  /** 装饰订单数据，添加 UI 所需字段 */
  _decorateOrder(raw) {
    const flowStatus = String(getOrderFlowStatus(raw));
    const statusText = fmtOrderStatus(raw);

    // 处理商品列表
    const decoratedItems = (raw.items || raw.goods || []).map((g, gi) => {
      const coverUrl = resolveGoodsImage(g);
      return {
        gkey: (g.id || gi) + '_' + Math.random().toString(36).slice(2, 6),
        name: g.name || g.goods_name || g.title || '商品',
        coverUrl: coverUrl,
        skuTags: parseSkuTags(g),
        priceText: fmtPrice(g.item_price || g.price || g.goods_price || 0),
        qtyText: g.total_num || g.quantity || g.num || 1,
        itemId: g.item_id,
      };
    });

    // 物流信息
    let deliveryText = '';
    let hasDelivery = false;
    if (raw.delivery && Array.isArray(raw.delivery) && raw.delivery.length) {
      hasDelivery = true;
      const d = raw.delivery[0];
      if (d.express_name && d.express_no) {
        deliveryText = d.express_name + '：' + d.express_no;
      } else if (d.express_no) {
        deliveryText = '快递单号：' + d.express_no;
      } else {
        deliveryText = '查看物流信息';
      }
    } else if (raw.express_no) {
      hasDelivery = true;
      deliveryText = '快递单号：' + raw.express_no;
    }

    // 支付方式
    let payMethodText = '';
    if (raw.pay_status == 20 || raw.pay_status == 2) {
      payMethodText = raw.pay_method || '微信支付';
    }

    return {
      ...raw,
      flowStatus: flowStatus,
      statusText: statusText,
      statusIcon: getStatusIcon(flowStatus),
      statusTip: getStatusTip(raw, flowStatus),
      decoratedItems: decoratedItems,
      prettyTotal: fmtPrice(raw.pay_price || raw.total_price || 0),
      prettyTotalPrice: fmtPrice(raw.total_price || 0),
      prettyExpress: fmtPrice(raw.express_price || 0),
      prettyCoupon: fmtPrice(raw.coupon_price || 0),
      prettyDiscount: fmtPrice(raw.discount_price || 0),
      prettyDate: fmtDate(raw.created_at),
      pay_time_text: fmtTime(raw.pay_time),
      delivery_time_text: fmtTime(raw.delivery_time),
      receipt_time_text: fmtTime(raw.receipt_time),
      pay_method_text: payMethodText,
      deliveryText: deliveryText,
      delivery: hasDelivery ? (raw.delivery || [{}]) : null,
      actions: buildActions(raw, flowStatus),
      store_name: raw.store_name || raw.merchant_name || raw.shop_name || '',
    };
  },

  /** 操作按钮点击 */
  onAction(e) {
    const action = e.currentTarget.dataset.action;
    if (!action) return;

    switch (action) {
      case 'pay':
        this._goPay();
        break;
      case 'cancel':
        this._cancelOrder();
        break;
      case 'confirm':
        this._confirmReceive();
        break;
      case 'remind':
        this._remindShip();
        break;
      case 'refund':
        this._goRefund();
        break;
      case 'review':
        wx.navigateTo({ url: '/pages/order/review/review?id=' + this.data.id });
        break;
      case 'reorder':
        this._reorder();
        break;
      case 'delete':
        this._deleteOrder();
        break;
      case 'logistics':
        this.onLogistics();
        break;
    }
  },

  async _goPay() {
    try {
      const res = await api.payment.create({
        order_id: this.data.id,
        payment_method: 'wechat',
      });
      if (res && res.timeStamp && res.nonceStr) {
        await this._callWxPay(res);
      } else if (res && res.pay_params) {
        await this._callWxPay(res.pay_params);
      }
      wx.showToast({ title: '支付成功', icon: 'success' });
      this.loadDetail();
    } catch (e) {
      if (e && e.errMsg && e.errMsg.indexOf('cancel') >= 0) return;
      wx.showToast({ title: e.message || '支付失败', icon: 'none' });
    }
  },

  _callWxPay(params) {
    return new Promise((resolve, reject) => {
      wx.requestPayment({
        timeStamp: params.timeStamp,
        nonceStr: params.nonceStr,
        package: params.package,
        signType: params.signType || 'MD5',
        paySign: params.paySign,
        success: resolve,
        fail: reject,
      });
    });
  },

  _cancelOrder() {
    wx.showModal({
      title: '提示',
      content: '确定要取消该订单吗？',
      success: async (r) => {
        if (!r.confirm) return;
        try {
          await api.order.cancel(this.data.id, '用户主动取消');
          wx.showToast({ title: '已取消', icon: 'success' });
          this.loadDetail();
        } catch (e) {
          wx.showToast({ title: e.message || '取消失败', icon: 'none' });
        }
      },
    });
  },

  _confirmReceive() {
    wx.showModal({
      title: '提示',
      content: '确认已收到商品？',
      success: async (r) => {
        if (!r.confirm) return;
        try {
          await api.order.confirm(this.data.id);
          wx.showToast({ title: '确认成功', icon: 'success' });
          this.loadDetail();
        } catch (e) {
          wx.showToast({ title: e.message || '操作失败', icon: 'none' });
        }
      },
    });
  },

  async _remindShip() {
    try {
      await api.order.remindShip(this.data.id);
      wx.showToast({ title: '已提醒发货', icon: 'success' });
    } catch (e) {
      wx.showToast({ title: e.message || '操作失败', icon: 'none' });
    }
  },

  _goRefund() {
    wx.navigateTo({ url: '/pages/order/refund-detail/refund-detail?id=' + this.data.id });
  },

  async _reorder() {
    try {
      const res = await api.order.reorder(this.data.id);
      if (res && res.cart_ids) {
        wx.switchTab({ url: '/pages/cart/cart' });
      } else {
        wx.showToast({ title: '已加入购物车', icon: 'success' });
      }
    } catch (e) {
      wx.showToast({ title: e.message || '操作失败', icon: 'none' });
    }
  },

  _deleteOrder() {
    wx.showModal({
      title: '提示',
      content: '确定要删除该订单吗？删除后不可恢复',
      success: async (r) => {
        if (!r.confirm) return;
        try {
          await api.order.delete(this.data.id);
          wx.showToast({ title: '已删除', icon: 'success' });
          setTimeout(() => {
            wx.navigateBack();
          }, 1000);
        } catch (e) {
          wx.showToast({ title: e.message || '删除失败', icon: 'none' });
        }
      },
    });
  },

  async onLogistics() {
    if (!this.data.order) return;
    var orderId = this.data.id;
    var expressName = this.data.order.express_name || '';
    var expressNo = this.data.order.express_no || '';
    wx.navigateTo({
      url: '/pages/logistics/logistics?order_id=' + orderId + '&express_name=' + expressName + '&express_no=' + expressNo,
    });
  },

  onCopyOrderNo() {
    if (!this.data.order || !this.data.order.order_no) return;
    wx.setClipboardData({
      data: String(this.data.order.order_no),
      success: () => {
        wx.showToast({ title: '已复制', icon: 'success' });
      },
    });
  },

  onContact() {
    wx.showModal({
      title: '联系客服',
      content: '客服热线：400-888-8888\n服务时间：周一至周日 9:00-22:00\n\n您也可以通过微信客服在线咨询。',
      confirmText: '在线咨询',
      confirmColor: '#2563eb',
      cancelText: '拨打热线',
      success: function (res) {
        if (res.confirm) {
          wx.openCustomerServiceChat({
            extInfo: { url: '' },
            corpId: '',
            fail: function () {
              wx.makePhoneCall({
                phoneNumber: '4008888888',
                fail: function () {},
              });
            },
          });
        } else if (res.cancel) {
          wx.makePhoneCall({
            phoneNumber: '4008888888',
            fail: function () {},
          });
        }
      },
    });
  },

  onBack() {
    wx.navigateBack();
  },
});
