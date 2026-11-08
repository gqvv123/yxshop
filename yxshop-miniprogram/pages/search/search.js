// pages/search/search.js
const storage = require('../../utils/storage');
const api = require('../../api/index');

Page({
  data: {
    statusBarHeight: 20,
    keyword: '',
    history: [],
    hotWords: [],
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.setData({ history: storage.get('searchHistory', []) });
    this.loadHotWords();
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/home/home' });
    }
  },

  async loadHotWords() {
    try {
      const r = await api.config.getSearchHotWords();
      this.setData({ hotWords: (r && r.words) || (Array.isArray(r) ? r : []) });
    } catch (e) { /* ignore */ }
  },

  onInput(e) {
    this.setData({ keyword: e.detail.value });
  },

  onConfirm() {
    const kw = (this.data.keyword || '').trim();
    if (!kw) return;
    const history = [kw, ...this.data.history.filter((x) => x !== kw)].slice(0, 10);
    storage.set('searchHistory', history);
    wx.navigateTo({ url: `/pages/item-list/item-list?keyword=${encodeURIComponent(kw)}` });
  },

  onTagTap(e) {
    const kw = e.currentTarget.dataset.kw;
    this.setData({ keyword: kw });
    this.onConfirm();
  },

  onClearHistory() {
    var self = this;
    wx.showModal({
      title: '提示',
      content: '确定清空搜索历史？',
      confirmColor: '#2563eb',
      success: function (r) {
        if (!r.confirm) return;
        storage.remove('searchHistory');
        self.setData({ history: [] });
        wx.showToast({ title: '已清空', icon: 'success' });
      },
    });
  },

  onClearInput() {
    this.setData({ keyword: '' });
  },
});
