// pages/order/list/list.js
const api = require('../../../api/index');
const auth = require('../../../utils/auth');
const imageUtil = require('../../../utils/image');
const { fmtOrderStatus, fmtPrice, fmtDate, getOrderFlowStatus } = require('../../../utils/format');

const TABS = [
  { key: 'all', label: '全部', flowStatus: null },
  { key: 'pending', label: '待付款', flowStatus: 10, badge: 0 },
  { key: 'paid', label: '待发货', flowStatus: 20, badge: 0 },
  { key: 'shipped', label: '待收货', flowStatus: 30, badge: 0 },
  { key: 'finished', label: '待评价', flowStatus: 40, badge: 0 },
  { key: 'refund', label: '退款/售后', flowStatus: 50 },
];

/** 状态 -> CSS class 映射 */
function getStatusClass(flowStatus) {
  const map = {
    10: 'pay',      // 待付款
    20: 'ship',     // 待发货
    30: 'recv',     // 待收货
    40: 'done',     // 已完成
    '-1': 'cancel', // 已取消
    50: 'refund',   // 退款
  };
  return map[flowStatus] || 'done';
}

/** 解析 SKU 标签 */
function parseSkuTags(g) {
  const tags = [];
  // 兼容多种字段
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
  // 如果有 sku_values 数组
  if (Array.isArray(g.sku_values)) {
    g.sku_values.forEach((v) => {
      if (v && tags.indexOf(v) < 0) tags.push(v);
    });
  }
  return tags;
}

/** 检查 URL 是否为无效图片路径（如 '0' 或以 /0 结尾） */
function isValidImageUrl(url) {
  if (!url) return false;
  var s = String(url).trim();
  if (!s || s === '0') return false;
  // 以 /0 结尾且不是合法图片扩展名
  if (/\/0$/.test(s) && !/\.(jpg|jpeg|png|gif|webp|bmp|svg)/i.test(s)) return false;
  return true;
}

/** 提取商品图片 URL，带兜底 */
function resolveGoodsImage(g) {
  // 直接字段
  if (isValidImageUrl(g.image)) return imageUtil.resolve(g.image);
  if (isValidImageUrl(g.goods_image)) return imageUtil.resolve(g.goods_image);
  if (isValidImageUrl(g.cover)) return imageUtil.resolve(g.cover);
  if (isValidImageUrl(g.pic)) return imageUtil.resolve(g.pic);
  if (isValidImageUrl(g.thumb)) return imageUtil.resolve(g.thumb);
  // images 数组
  if (Array.isArray(g.images) && g.images.length) {
    var img = g.images[0];
    if (img && isValidImageUrl(img.url)) return imageUtil.resolve(img.url);
    if (img && isValidImageUrl(img.image_id)) return imageUtil.resolve(img.image_id);
    if (img && typeof img === 'string' && isValidImageUrl(img)) return imageUtil.resolve(img);
  }
  // 从关联商品 item 取主图
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

/** 根据订单状态生成操作按钮 */
function buildActions(order, flowStatus) {
  const actions = [];

  switch (flowStatus) {
    case 10: // 待付款
      actions.push({ action: 'cancel', label: '取消订单', type: 'default' });
      actions.push({ action: 'pay', label: '立即付款', type: 'primary', icon: 'tag', iconColor: '#fff' });
      break;
    case 20: // 待发货
      actions.push({ action: 'remind', label: '提醒发货', type: 'default' });
      actions.push({ action: 'detail', label: '查看详情', type: 'default' });
      break;
    case 30: // 待收货
      actions.push({ action: 'logistics', label: '查看物流', type: 'default' });
      actions.push({ action: 'confirm', label: '确认收货', type: 'primary' });
      break;
    case 40: // 已完成 / 待评价
      actions.push({ action: 'detail', label: '查看详情', type: 'default' });
      if (order.is_reviewed) {
        actions.push({ action: 'reorder', label: '再次购买', type: 'primary' });
      } else {
        actions.push({ action: 'refund', label: '申请售后', type: 'danger' });
        actions.push({ action: 'review', label: '评价', type: 'primary', icon: 'star', iconColor: '#fff' });
      }
      break;
    case '-1': // 已取消
      actions.push({ action: 'detail', label: '查看详情', type: 'default' });
      actions.push({ action: 'reorder', label: '再次购买', type: 'primary' });
      break;
    case 50: // 退款
      actions.push({ action: 'refundDetail', label: '退款详情', type: 'default' });
      if (order.refund_status === 0 || order.refund_status === 'pending') {
        actions.push({ action: 'cancelRefund', label: '撤销申请', type: 'danger' });
      }
      break;
    default:
      actions.push({ action: 'detail', label: '查看详情', type: 'default' });
  }

  return actions;
}

/** 获取底部标签文案 */
function getFooterLabel(flowStatus) {
  const map = {
    10: '共{{count}}件 实付款',
    20: '共{{count}}件 实付款',
    30: '共{{count}}件 实付款',
    40: '共{{count}}件 实付款',
    '-1': '共{{count}}件',
    50: '退款金额',
  };
  return map[flowStatus] || '实付款';
}

Page({
  data: {
    tabs: TABS,
    current: 0,
    items: [],
    page: 1,
    pageSize: 20,
    finished: false,
    loading: false,
    statusBarHeight: 20,
  },

  onLoad(options) {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    // 支持 ?status=10 等直接定位 Tab
    const target = String(options.status || '');
    const idx = TABS.findIndex((t) => String(t.flowStatus) === target);
    if (idx >= 0) this.setData({ current: idx });
    this.refresh();

    // 加载各状态数量
    this.loadStatusCounts();
  },

  onShow() {
    // 同步自定义 tabbar 选中状态
    const tabBar = this.getTabBar && this.getTabBar();
    if (tabBar) tabBar.setSelected();

    // 检查是否有来自 profile 页面的目标状态
    try {
      const targetStatus = wx.getStorageSync('order_list_target_status');
      if (targetStatus) {
        wx.removeStorageSync('order_list_target_status');
        const idx = TABS.findIndex((t) => String(t.flowStatus) === String(targetStatus));
        if (idx >= 0 && idx !== this.data.current) {
          this.setData({ current: idx });
          this.refresh();
          return;
        }
      }
    } catch (e) {}

    // 从订单详情返回时刷新
    if (this.data.items.length) this.refresh();
  },

  async loadStatusCounts() {
    if (!auth.isLoggedIn()) return;
    try {
      const r = await api.order.statusCounts();
      if (!r) return;
      const tabs = this.data.tabs.map((t) => {
        if (t.key === 'pending') t.badge = r.pending_payment || 0;
        else if (t.key === 'paid') t.badge = r.pending_ship || 0;
        else if (t.key === 'shipped') t.badge = r.pending_receive || 0;
        else if (t.key === 'finished') t.badge = r.pending_review || 0;
        return t;
      });
      this.setData({ tabs });
    } catch (e) { /* ignore */ }
  },

  onTabTap(e) {
    const i = e.currentTarget.dataset.index;
    if (i === this.data.current) return;
    this.setData({ current: i });
    this.refresh();
  },

  async refresh() {
    this.setData({ page: 1, items: [], finished: false });
    await this.loadMore();
  },

  async loadMore() {
    if (this.data.loading || this.data.finished) return;
    this.setData({ loading: true });
    try {
      const tab = this.data.tabs[this.data.current];
      const params = { page: this.data.page, page_size: this.data.pageSize };

      // 退款 Tab 特殊处理
      if (tab.key === 'refund') {
        params.refund = 1;
      } else if (tab.flowStatus !== null) {
        // 后端按 status 字段筛选
        if (tab.flowStatus === 10) params.status = 10;
        else if (tab.flowStatus === 40) params.status = 30;
        else if (tab.flowStatus === 20 || tab.flowStatus === 30) params.status = 10;
        else if (tab.flowStatus === '-1' || tab.flowStatus === -1) params.status = 20;
      }

      const res = await api.order.getList(params);
      let list = (res && (res.data || res.list || res.items)) || (Array.isArray(res) ? res : []);

      // 前端按流程状态过滤（非退款 Tab）
      if (tab.key !== 'refund' && tab.flowStatus !== null) {
        list = list.filter((o) => Number(getOrderFlowStatus(o)) === Number(tab.flowStatus));
      }

      const decorated = list.map((o) => this._decorateOrder(o));

      this.setData({
        items: this.data.items.concat(decorated),
        page: this.data.page + 1,
        finished: list.length < this.data.pageSize,
      });
    } catch (e) {
      this.setData({ finished: true });
    } finally {
      this.setData({ loading: false });
    }
  },

  /** 装饰单个订单，添加 UI 所需字段 */
  _decorateOrder(o) {
    const flowStatus = String(getOrderFlowStatus(o));
    const statusText = fmtOrderStatus(o);

    // 退款状态覆盖
    let displayStatus = flowStatus;
    let displayText = statusText;
    if (o.refund_status != null && o.refund_status !== '' && Number(o.refund_status) >= 0) {
      displayStatus = '50';
      if (o.refund_status === 0 || o.refund_status === 'pending') {
        displayText = '退款中';
      } else if (o.refund_status === 1 || o.refund_status === 'approved') {
        displayText = '退款成功';
      } else if (o.refund_status === 2 || o.refund_status === 'rejected') {
        displayText = '退款失败';
      }
    }

    // 处理商品
    const goods = (o.items || o.goods || []).map((g, gi) => {
      const coverUrl = resolveGoodsImage(g);
      return {
        gkey: (g.id || gi) + '_' + Math.random().toString(36).slice(2, 6),
        name: g.name || g.goods_name || g.title || '商品',
        coverUrl: coverUrl,
        skuTags: parseSkuTags(g),
        priceText: fmtPrice(g.item_price || g.price || g.goods_price || 0),
        qtyText: g.total_num || g.quantity || g.num || 1,
      };
    });

    // 计算总件数
    const totalCount = goods.reduce((sum, g) => sum + Number(g.qtyText), 0);

    // 底部标签
    let footerLabel = getFooterLabel(displayStatus);
    footerLabel = footerLabel.replace('{{count}}', totalCount);

    // 退款金额
    let prettyTotal = fmtPrice(o.pay_price || o.total_price || 0);
    if (displayStatus === '50' && o.refund_amount) {
      prettyTotal = fmtPrice(o.refund_amount);
    }

    return {
      ...o,
      key: (o.order_id || o.id) + '_' + this.data.page + '_' + Math.random().toString(36).slice(2, 6),
      statusText: displayText,
      statusClass: getStatusClass(displayStatus),
      flowStatus: displayStatus,
      items: goods,
      prettyTotal: prettyTotal,
      prettyDate: fmtDate(o.created_at),
      footerLabel: footerLabel,
      actions: buildActions(o, displayStatus),
      store_name: o.store_name || o.merchant_name || o.shop_name || '',
    };
  },

  onReachBottom() {
    this.loadMore();
  },

  onItemTap(e) {
    const id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: `/pages/order/detail/detail?id=${id}` });
  },

  /** 操作按钮点击 */
  onAction(e) {
    const action = e.currentTarget.dataset.action;
    const id = e.currentTarget.dataset.id;
    if (!action || !id) return;

    switch (action) {
      case 'pay':
        this._goPay(id);
        break;
      case 'cancel':
        this._cancelOrder(id);
        break;
      case 'confirm':
        this._confirmReceive(id);
        break;
      case 'remind':
        this._remindShip(id);
        break;
      case 'review':
        wx.navigateTo({ url: `/pages/order/review/review?id=${id}` });
        break;
      case 'refund':
      case 'refundDetail':
        wx.navigateTo({ url: `/pages/order/refund-detail/refund-detail?id=${id}` });
        break;
      case 'reorder':
        this._reorder(id);
        break;
      case 'logistics':
        wx.navigateTo({ url: `/pages/logistics/logistics?order_id=${id}` });
        break;
      case 'cancelRefund':
        this._cancelRefund(id);
        break;
      case 'detail':
      default:
        wx.navigateTo({ url: `/pages/order/detail/detail?id=${id}` });
    }
  },

  async _goPay(id) {
    try {
      const res = await api.payment.pay({ order_id: id });
      if (res && res.pay_params) {
        await this._callWxPay(res.pay_params);
      } else if (res && res.payment) {
        await this._callWxPay(res.payment);
      }
      wx.showToast({ title: '支付成功', icon: 'success' });
      this.refresh();
    } catch (e) {
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

  _cancelOrder(id) {
    wx.showModal({
      title: '提示',
      content: '确定要取消该订单吗？',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await api.order.cancel(id, '用户主动取消');
          wx.showToast({ title: '已取消', icon: 'success' });
          this.refresh();
        } catch (e) {
          wx.showToast({ title: e.message || '取消失败', icon: 'none' });
        }
      },
    });
  },

  _confirmReceive(id) {
    wx.showModal({
      title: '提示',
      content: '确认已收到商品？',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await api.order.confirm(id);
          wx.showToast({ title: '确认成功', icon: 'success' });
          this.refresh();
        } catch (e) {
          wx.showToast({ title: e.message || '操作失败', icon: 'none' });
        }
      },
    });
  },

  async _remindShip(id) {
    try {
      await api.order.remindShip(id);
      wx.showToast({ title: '已提醒发货', icon: 'success' });
    } catch (e) {
      wx.showToast({ title: e.message || '操作失败', icon: 'none' });
    }
  },

  async _reorder(id) {
    try {
      const res = await api.order.reorder(id);
      if (res && res.cart_ids) {
        wx.switchTab({ url: '/pages/cart/cart' });
      } else {
        wx.navigateTo({ url: `/pages/order/detail/detail?id=${id}` });
      }
    } catch (e) {
      wx.showToast({ title: e.message || '操作失败', icon: 'none' });
    }
  },

  _cancelRefund(id) {
    wx.showModal({
      title: '提示',
      content: '确定要撤销退款申请吗？',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await api.order.refund({ id: id, action: 'cancel' });
          wx.showToast({ title: '已撤销', icon: 'success' });
          this.refresh();
        } catch (e) {
          wx.showToast({ title: e.message || '操作失败', icon: 'none' });
        }
      },
    });
  },

  onBack() {
    wx.navigateBack();
  },

  onSearch() {
    wx.navigateTo({ url: '/pages/search/search' });
  },

  onPullDownRefresh() {
    this.refresh().finally(() => {
      wx.stopPullDownRefresh();
    });
  },
});
