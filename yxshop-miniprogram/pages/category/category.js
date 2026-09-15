// pages/category/category.js
const api = require('../../api/index');
const auth = require('../../utils/auth');
const config = require('../../config/index');

Page({
  data: {
    categories: [],
    currentIndex: 0,
    currentCategory: {},
    subCategories: [],
    activeSubId: 0,
    items: [],
    loading: false,
    statusBarHeight: 20,
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.loadCategories();
  },

  onShow() {
    this.syncTabBar();
  },

  /** 同步自定义 tabbar 选中状态 */
  syncTabBar() {
    const tabBar = this.getTabBar && this.getTabBar();
    if (tabBar) tabBar.setSelected();
  },

  async loadCategories() {
    try {
      const res = await api.category.tree().catch(() => null);
      const list = this.normalizeList(res);

      // 在最前面插入「推荐」虚拟分类
      const categories = [
        { id: 0, name: '推荐', desc: '为你精选好物', children: [] },
        ...list,
      ];

      this.setData({ categories });

      if (categories.length > 0) {
        this.selectCategory(0, categories[0]);
      }
    } catch (e) {
      // 回退：仅展示空态
    }
  },

  normalizeList(input) {
    if (Array.isArray(input)) return input;
    if (input && Array.isArray(input.data)) return input.data;
    if (input && Array.isArray(input.list)) return input.list;
    return [];
  },

  /** 点击左侧一级分类 */
  async onCategoryTap(e) {
    const idx = e.currentTarget.dataset.index;
    if (idx === this.data.currentIndex) return;
    this.selectCategory(idx, this.data.categories[idx]);
  },

  /** 选中某个分类（统一入口） */
  selectCategory(idx, cat) {
    if (!cat) return;
    const subCategories = cat.children || [];
    this.setData({
      currentIndex: idx,
      currentCategory: cat,
      subCategories,
      activeSubId: 0,
      items: [],
    });
    this.loadItems(cat, 0);
  },

  /** 点击子分类 */
  async onSubCategoryTap(e) {
    const subId = e.currentTarget.dataset.id;
    if (subId === this.data.activeSubId) return;
    this.setData({ activeSubId: subId });
    const subCat = this.data.subCategories.find((s) => s.id === subId);
    this.loadItems(subCat || this.data.currentCategory, subId);
  },

  /**
   * 加载商品列表
   * @param {Object} cat 分类对象
   * @param {Number} subId 子分类ID（0表示加载整个一级分类）
   */
  async loadItems(cat, subId) {
    if (!cat) return;
    this.setData({ loading: true, items: [] });

    try {
      let list = [];

      if (cat.id === 0 || (!cat.id && !subId)) {
        // 推荐：加载热门/推荐商品
        const res = await api.item.getHot({ page: 1, page_size: 20 }).catch(() => null);
        list = this.normalizeList(res);
      } else {
        // 按分类加载
        const categoryId = subId || cat.id;
        const res = await api.category.items({ category_id: categoryId, page: 1, page_size: 20 }).catch(() => null);
        list = this.normalizeList(res);
      }

      this.setData({ items: list });
    } catch (e) {
      wx.showToast({ title: '加载失败', icon: 'none' });
    } finally {
      this.setData({ loading: false });
    }
  },

  /** 加入购物车 */
  async onAddCart(e) {
    const { id } = e.detail;
    if (!id) return;

    // 未登录时引导登录
    if (!auth.isLoggedIn()) {
      wx.showModal({
        title: '提示',
        content: '请先登录后再加入购物车',
        confirmText: '去登录',
        success: (modalRes) => {
          if (modalRes.confirm) {
            wx.navigateTo({ url: '/pages/login/login' });
          }
        },
      });
      return;
    }

    try {
      await api.cart.add({ item_id: id, quantity: 1, app_id: config.appId });
      wx.showToast({ title: '已加入购物车', icon: 'success' });
      // 更新购物车角标
      const tabBar = this.getTabBar && this.getTabBar();
      if (tabBar && typeof tabBar.refreshCartCount === 'function') {
        tabBar.refreshCartCount();
      }
    } catch (e) {
      // 请求层已弹出 toast（含未登录 401 自动跳登录）
    }
  },

  onSearchTap() {
    wx.navigateTo({ url: '/pages/search/search' });
  },

  /** 下拉刷新 */
  onPullDownRefresh() {
    this.loadCategories().then(() => {
      wx.stopPullDownRefresh();
    });
  },
});
