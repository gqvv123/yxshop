const api = require('../../api/index');
const imageUtil = require('../../utils/image');

/** 格式化浏览时间，兼容 Unix 时间戳和日期字符串 */
function formatTime(ts) {
  if (!ts) return '';
  var num = Number(ts);
  // 如果 ts 是日期字符串（如 "2026-08-09 18:39:03"），解析为时间戳
  if (isNaN(num)) {
    var parsed = Date.parse(String(ts).replace(/-/g, '/'));
    if (isNaN(parsed)) return '';
    num = Math.floor(parsed / 1000);
  }
  var now = Math.floor(Date.now() / 1000);
  var diff = now - num;
  if (diff < 0) diff = 0;
  if (diff < 60) return '刚刚';
  if (diff < 3600) return Math.floor(diff / 60) + '分钟前';
  if (diff < 86400) return Math.floor(diff / 3600) + '小时前';
  if (diff < 2592000) return Math.floor(diff / 86400) + '天前';
  var d = new Date(num * 1000);
  return (d.getMonth() + 1) + '月' + d.getDate() + '日';
}

Page({
  data: {
    statusBarHeight: 20,
    list: [],
    total: 0,
    page: 1,
    hasMore: true,
    loading: false,
  },

  onLoad() {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.load();
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  load() {
    var self = this;
    if (self.data.loading || !self.data.hasMore) return;
    self.setData({ loading: true });

    api.user.getFootprint({ page: self.data.page, page_size: 20 })
      .then(function (r) {
        var result = (r && r.data) || r || {};
        var rawList = result.list || [];
        var items = rawList.map(function (fp) {
          var item = fp.item || {};
          return {
            view_id: fp.view_id,
            item_id: item.id,
            name: item.name || item.goods_name || '商品名称',
            coverUrl: imageUtil.getItemCover(item),
            price: item.sale_price || item.price || '0.00',
            view_count: fp.view_count || 1,
            viewed_at_text: formatTime(fp.viewed_at),
          };
        });
        var list = self.data.page === 1 ? items : self.data.list.concat(items);
        self.setData({
          list: list,
          total: result.total || list.length,
          hasMore: items.length >= 20,
          page: self.data.page + 1,
          loading: false,
        });
      })
      .catch(function () {
        self.setData({ list: [], loading: false });
      });
  },

  onReachBottom() {
    this.load();
  },

  onItemTap(e) {
    var id = e.currentTarget.dataset.id;
    if (id) {
      wx.navigateTo({ url: '/pages/item-detail/item-detail?id=' + id });
    }
  },

  onRemoveOne(e) {
    var self = this;
    var viewId = e.currentTarget.dataset.viewId;
    wx.showModal({
      title: '提示',
      content: '确定删除该浏览记录？',
      confirmColor: '#dc2626',
      success: function (r) {
        if (!r.confirm) return;
        api.user.removeFootprint(viewId).then(function () {
          var list = self.data.list.filter(function (it) { return it.view_id != viewId; });
          self.setData({ list: list, total: self.data.total - 1 });
          wx.showToast({ title: '已删除', icon: 'success' });
        }).catch(function () {});
      },
    });
  },

  onClearAll() {
    var self = this;
    wx.showModal({
      title: '提示',
      content: '确定清空所有浏览记录？此操作不可恢复。',
      confirmColor: '#dc2626',
      success: function (r) {
        if (!r.confirm) return;
        wx.showLoading({ title: '清空中...' });
        api.user.clearFootprint().then(function () {
          wx.hideLoading();
          wx.showToast({ title: '已清空', icon: 'success' });
          self.setData({ list: [], total: 0, page: 1, hasMore: true });
        }).catch(function () {
          wx.hideLoading();
        });
      },
    });
  },
});
