const api = require('../../api/index');

Page({
  data: {
    statusBarHeight: 20,
    orderId: '',
    expressName: '',
    expressNo: '',
    traces: [],
    loading: true,
    copySuccess: false,
  },

  onLoad(options) {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    var orderId = options.order_id || options.id || '';
    var expressName = options.express_name || '';
    var expressNo = options.express_no || '';

    this.setData({
      orderId: orderId,
      expressName: expressName,
      expressNo: expressNo,
    });

    if (expressNo) {
      // 如果已有快递单号，直接查询
      this.queryLogistics();
    } else if (orderId) {
      // 通过订单ID查询
      this.loadFromOrder();
    } else {
      this.setData({ loading: false });
    }
  },

  async loadFromOrder() {
    try {
      var res = await api.order.getLogistics(this.data.orderId);
      var data = (res && res.data) || res || {};
      this.setData({
        expressName: data.express_name || data.expressName || '',
        expressNo: data.express_no || data.expressNo || '',
      });
      if (data.traces) {
        this.processTraces(data.traces);
      }
      this.setData({ loading: false });
    } catch (e) {
      this.setData({ loading: false });
    }
  },

  async queryLogistics() {
    this.setData({ loading: true });
    try {
      var res = await api.order.getLogistics(this.data.orderId);
      var data = (res && res.data) || res || {};
      if (data.traces) {
        this.processTraces(data.traces);
      } else {
        // 如果后端没有返回 traces，使用 mock 数据
        this.setData({
          traces: [{
            time: this._formatNow(),
            content: '物流信息查询中，请稍后刷新',
            isLatest: true,
          }],
        });
      }
      this.setData({ loading: false });
    } catch (e) {
      this.setData({
        loading: false,
        traces: [{
          time: this._formatNow(),
          content: '物流信息查询失败，请稍后重试',
          isLatest: true,
        }],
      });
    }
  },

  processTraces(traces) {
    if (!Array.isArray(traces)) traces = [];
    var processed = traces.map(function (t, idx) {
      return {
        time: t.time || t.acceptTime || t.created_at || '',
        content: t.content || t.acceptStation || t.context || '',
        location: t.location || '',
        isLatest: idx === 0,
      };
    });
    this.setData({ traces: processed });
  },

  _formatNow() {
    var d = new Date();
    var pad = function (n) { return n < 10 ? '0' + n : '' + n; };
    return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate()) +
      ' ' + pad(d.getHours()) + ':' + pad(d.getMinutes()) + ':' + pad(d.getSeconds());
  },

  onCopyExpressNo() {
    if (!this.data.expressNo) return;
    var self = this;
    wx.setClipboardData({
      data: this.data.expressNo,
      success: function () {
        self.setData({ copySuccess: true });
        setTimeout(function () {
          self.setData({ copySuccess: false });
        }, 2000);
        wx.showToast({ title: '已复制', icon: 'success' });
      },
    });
  },

  onRefresh() {
    this.queryLogistics();
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },
});
