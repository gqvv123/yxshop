const api = require('../../../api/index');
const imageUtil = require('../../../utils/image');

Page({
  data: {
    statusBarHeight: 20,
    list: [],
    page: 1,
    hasMore: true,
    loading: false,
  },

  onLoad() {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.loadList();
  },

  onPullDownRefresh() {
    this.setData({ list: [], page: 1, hasMore: true });
    this.loadList().finally(function () {
      wx.stopPullDownRefresh();
    });
  },

  onReachBottom() {
    this.loadList();
  },

  async loadList() {
    if (this.data.loading || !this.data.hasMore) return;
    this.setData({ loading: true });
    try {
      var res = await api.point.getRecords({ page: this.data.page, page_size: 20 });
      var result = (res && res.data) || res || {};
      var rawItems = Array.isArray(result) ? result : (result.list || result.data || []);
      var items = rawItems.map(function (item) {
        return Object.assign({}, item, {
          coverUrl: imageUtil.getItemCover ? imageUtil.getItemCover(item) : '',
          statusText: _statusText(item.status),
          statusClass: _statusClass(item.status),
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
      this.setData({ loading: false, list: [] });
    }
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

function _statusText(status) {
  var map = { 0: '待处理', 1: '已发货', 2: '已完成', 3: '已取消', 4: '已发放' };
  return map[status] || '处理中';
}

function _statusClass(status) {
  var map = { 0: 'pending', 1: 'shipped', 2: 'done', 3: 'cancelled', 4: 'done' };
  return map[status] || 'pending';
}
