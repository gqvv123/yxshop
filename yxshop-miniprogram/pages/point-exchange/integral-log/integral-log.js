const api = require('../../../api/index');

Page({
  data: {
    statusBarHeight: 20,
    stats: { totalGain: 0, totalUsed: 0, monthGain: 0 },
    list: [],
    page: 1,
    hasMore: true,
    loading: false,
    activeTab: 0,
    tabs: [
      { value: 'all', label: '全部' },
      { value: 'gain', label: '获得' },
      { value: 'used', label: '消耗' },
    ],
  },

  onLoad() {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.loadStats();
    this.loadList();
  },

  onPullDownRefresh() {
    this.setData({ list: [], page: 1, hasMore: true });
    Promise.all([this.loadStats(), this.loadList()]).catch(function () {}).finally(function () {
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    this.loadList();
  },

  async loadStats() {
    try {
      var res = await api.point.getIntegralStats();
      var data = (res && res.data) || res || {};
      this.setData({
        stats: {
          totalGain: data.total_earned || data.total_gain || data.totalGain || 0,
          totalUsed: data.total_spent || data.total_used || data.totalUsed || 0,
          monthGain: data.month_earned || data.month_gain || data.monthGain || 0,
        },
      });
    } catch (e) {}
  },

  async loadList() {
    if (this.data.loading || !this.data.hasMore) return;
    this.setData({ loading: true });
    try {
      var params = { page: this.data.page, page_size: 20 };
      var tabVal = this.data.tabs[this.data.activeTab].value;
      if (tabVal === 'gain') params.type = 'gain';
      if (tabVal === 'used') params.type = 'used';

      var res = await api.point.getIntegralLog(params);
      var result = (res && res.data) || res || {};
      var rawItems = Array.isArray(result) ? result : (result.list || result.data || []);
      var items = rawItems.map(function (item) {
        var isGain = (item.type === 'gain' || item.change > 0 || item.points > 0);
        return Object.assign({}, item, {
          isGain: isGain,
          changeText: (isGain ? '+' : '-') + Math.abs(item.change || item.points || 0),
        });
      });
      var list = this.data.page === 1 ? items : this.data.list.concat(items);
      this.setData({
        list: list,
        hasMore: items.length >= 20,
        page: this.data.page + 1,
        loading: false,
      });
    } catch (e) {
      // 仅重置 loading 状态，保留已加载的数据（避免翻页失败时清空列表）
      this.setData({ loading: false });
    }
  },

  onTabChange(e) {
    var idx = Number(e.currentTarget.dataset.idx);
    if (idx === this.data.activeTab) return;
    this.setData({ activeTab: idx, list: [], page: 1, hasMore: true });
    this.loadList();
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
