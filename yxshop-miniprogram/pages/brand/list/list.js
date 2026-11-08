/**
 * 品牌专区
 * 对应管理端「商品管理 → 商品品牌」
 * 数据源：GET /api/v1/brand/list
 */
const api = require('../../../api/index');

Page({
  data: {
    statusBarHeight: 20,
    loading: true,
    loadError: '',
    brands: [],
    keyword: '',
    all: [],
  },

  onLoad(options) {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    if (options && options.category_id) {
      this.setData({ categoryId: options.category_id });
      this.loadByCategory(options.category_id);
      return;
    }
    this.load();
  },

  onPullDownRefresh() {
    this.load().then(() => wx.stopPullDownRefresh());
  },

  async load() {
    this.setData({ loading: true, loadError: '' });
    try {
      const res = await api.brand.getList({ limit: 50 });
      const list = (Array.isArray(res) ? res : (res && (res.list || res.data)) || []).map((b) => ({
        id: b.id,
        name: b.name || '',
        logo: b.logo || '',
        desc: b.desc || '',
      }));
      this.setData({ brands: list, all: list, loading: false });
    } catch (e) {
      this.setData({ loading: false, loadError: (e && e.message) || '加载失败' });
    }
  },

  /** 按分类取品牌（管理端「商品分类」下钻） */
  async loadByCategory(categoryId) {
    this.setData({ loading: true, loadError: '' });
    try {
      const res = await api.category.brands({ category_id: categoryId });
      const list = (Array.isArray(res) ? res : (res && (res.list || res.data)) || []).map((b) => ({
        id: b.id || b.brand_id,
        name: b.name || b.brand_name || '',
        logo: b.logo || '',
        desc: b.desc || '',
      }));
      this.setData({ brands: list, all: list, loading: false });
    } catch (e) {
      this.setData({ loading: false, loadError: (e && e.message) || '加载失败' });
    }
  },

  onSearchInput(e) {
    const kw = (e.detail.value || '').trim();
    const all = this.data.all || [];
    this.setData({
      keyword: kw,
      brands: kw ? all.filter((b) => b.name.indexOf(kw) >= 0) : all,
    });
  },

  onClearKeyword() {
    this.setData({ keyword: '', brands: this.data.all || [] });
  },

  onTapBrand(e) {
    const { id, name } = e.currentTarget.dataset;
    if (!id) return;
    wx.navigateTo({
      url: `/pages/item-list/item-list?brand_id=${id}&brand_name=${encodeURIComponent(name || '')}`,
    });
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/home/home' });
    }
  },
});
