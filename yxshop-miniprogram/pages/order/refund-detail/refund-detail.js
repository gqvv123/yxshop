const api = require('../../../api/index');
const { fmtPrice, fmtDate } = require('../../../utils/format');

Page({
  data: {
    statusBarHeight: 20,
    orderId: 0,
    refundStatus: 0,
    refundAmount: '0.00',
    timeline: [],
    loading: true,
  },

  onLoad(options) {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    const orderId = options.id || '';
    if (!orderId) {
      wx.showToast({ title: '订单不存在', icon: 'none' });
      return;
    }
    this.setData({ orderId });
    this.loadDetail();
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  async loadDetail() {
    this.setData({ loading: true });
    try {
      const r = await api.order.refundDetail({ id: this.data.orderId });
      const timeline = (r.timeline || []).map((t) => ({
        ...t,
        timeText: fmtDate(t.time),
      }));
      this.setData({
        refundStatus: r.refund_status || 0,
        refundAmount: fmtPrice(r.refund_amount || 0),
        timeline,
      });
    } catch (e) {
      try {
        const order = await api.order.getDetail(this.data.orderId);
        this.setData({
          refundStatus: order.pay_status || 0,
          refundAmount: fmtPrice(order.pay_price || 0),
          timeline: [],
        });
      } catch (e2) { /* ignore */ }
    } finally {
      this.setData({ loading: false });
    }
  },
});