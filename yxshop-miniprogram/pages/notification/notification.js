// pages/notification/notification.js
const api = require('../../api/index');
const { fmtDate } = require('../../utils/format');

Page({
  data: {
    statusBarHeight: 20,
    items: [],
    loading: true,
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
  },

  onShow() { this.load(); },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  async load() {
    this.setData({ loading: true });
    try {
      const res = await api.notification.getList();
      const arr = Array.isArray(res) ? res : (res && (res.data || res.list)) || [];
      const items = arr.map((n) => ({ ...n, prettyDate: fmtDate(n.created_at) }));
      this.setData({ items });
    } finally {
      this.setData({ loading: false });
    }
  },

  async onTap(e) {
    const id = e.currentTarget.dataset.id;
    try { await api.notification.markRead(id); } catch (e) { /* ignore */ }
    const item = this.data.items.find((x) => x.id == id);
    if (item) {
      item.is_read = 1;
      this.setData({ items: [...this.data.items] });
    }
  },

  onReadAll() {
    var self = this;
    wx.showModal({
      title: '提示',
      content: '确定将所有消息标记为已读？',
      confirmColor: '#2563eb',
      success: function (r) {
        if (!r.confirm) return;
        api.notification.markAllRead().then(function () {
          var items = self.data.items.map(function (it) {
            return Object.assign({}, it, { is_read: 1 });
          });
          self.setData({ items: items });
          wx.showToast({ title: '全部已读', icon: 'success' });
        }).catch(function () {});
      },
    });
  },
});
