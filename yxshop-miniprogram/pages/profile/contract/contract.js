const api = require('../../../api/index');

Page({
  data: {
    statusBarHeight: 20,
    stats: { total: 0, signed: 0, pending: 0, expired: 0 },
    contracts: [],
    loading: false,
    showDetail: false,
    current: {},
  },

  onLoad() {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.loadList();
    this.loadStats();
  },

  onPullDownRefresh() {
    Promise.all([this.loadList(), this.loadStats()]).finally(function () {
      wx.stopPullDownRefresh();
    });
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  noop() {},

  async loadList() {
    this.setData({ loading: true });
    try {
      var res = await api.contract.myList({ page: 1, page_size: 50 });
      var list = [];
      if (res && Array.isArray(res.data)) {
        list = res.data;
      } else if (Array.isArray(res)) {
        list = res;
      }
      this.setData({ contracts: list });
    } catch (e) {
      // ignore
    } finally {
      this.setData({ loading: false });
    }
  },

  async loadStats() {
    try {
      var res = await api.contract.stats();
      var stats = (res && res.data) ? res.data : (res || {});
      this.setData({ stats: stats });
    } catch (e) {
      // ignore
    }
  },

  async onViewContract(e) {
    var id = e.currentTarget.dataset.id;
    if (!id) return;
    var cached = this.data.contracts.find(function (c) { return c.id === id; });
    if (cached) {
      this.formatAndShowDetail(cached);
    }
    try {
      var res = await api.contract.detail({ id: id });
      var detail = (res && res.data) ? res.data : (res || {});
      this.formatAndShowDetail(detail);
    } catch (e) {
      if (!cached) {
        wx.showToast({ title: '加载失败', icon: 'none' });
      }
    }
  },

  formatAndShowDetail(detail) {
    var formatted = Object.assign({}, detail);
    if (detail.signed_at && detail.signed_at > 0) {
      var d = new Date(detail.signed_at * 1000);
      formatted.signed_text = d.getFullYear() + '-' +
        String(d.getMonth() + 1).padStart(2, '0') + '-' +
        String(d.getDate()).padStart(2, '0');
    } else {
      formatted.signed_text = '';
    }
    this.setData({ showDetail: true, current: formatted });
  },

  hideDetail() {
    this.setData({ showDetail: false });
  },

  onPreviewFile() {
    var url = this.data.current.file_url;
    if (!url) return;
    wx.showLoading({ title: '加载中...' });
    wx.downloadFile({
      url: url,
      success: function (res) {
        wx.hideLoading();
        wx.openDocument({
          filePath: res.tempFilePath,
          fail: function () {
            wx.showToast({ title: '无法打开文件', icon: 'none' });
          },
        });
      },
      fail: function () {
        wx.hideLoading();
        wx.showToast({ title: '下载失败', icon: 'none' });
      },
    });
  },
});
