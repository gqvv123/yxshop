// pages/item-list/item-list.js
const api = require('../../api/index');

Page({
  data: {
    type: '', // hot | new | recommend | ''
    keyword: '',
    items: [],
    page: 1,
    pageSize: 20,
    finished: false,
    loading: false,
    title: '商品列表',
    sortType: 'default', // default | sales | price
    sortPriceAsc: false,
    isGridLayout: true, // true=双列, false=单列
  },

  onLoad(options) {
    const type = options.type || '';
    const keyword = options.keyword || '';
    const map = { hot: '热销爆款', new: '上新好物', recommend: '为你推荐' };
    this.setData({
      type,
      keyword,
      title: keyword ? `搜索：${keyword}` : (map[type] || '商品列表'),
    });
    wx.setNavigationBarTitle({ title: this.data.title });
    this.refresh();
  },

  onSortChange(e) {
    const sort = e.currentTarget.dataset.sort;
    if (sort === 'price' && this.data.sortType === 'price') {
      this.setData({ sortPriceAsc: !this.data.sortPriceAsc });
    } else {
      this.setData({ sortType: sort, sortPriceAsc: false });
    }
    this.refresh();
  },

  onToggleLayout() {
    this.setData({ isGridLayout: !this.data.isGridLayout });
  },

  async refresh() {
    this.setData({ page: 1, items: [], finished: false });
    await this.loadMore();
  },

  async loadMore() {
    if (this.data.loading || this.data.finished) return;
    this.setData({ loading: true });
    try {
      let res;
      const page = this.data.page;
      const pageSize = this.data.pageSize;

      const appId = (getApp() && getApp().globalData && getApp().globalData.appId) || 10001;
      if (this.data.type === 'hot') {
        res = await api.item.getHot({ page, limit: pageSize, app_id: appId });
      } else if (this.data.type === 'new') {
        res = await api.item.getNew({ page, limit: pageSize, app_id: appId });
      } else if (this.data.type === 'recommend') {
        res = await api.item.getRecommended({ page, limit: pageSize, app_id: appId });
      } else {
        res = await api.item.getList({ page, page_size: pageSize, keyword: this.data.keyword, app_id: appId });
      }

      let list = this.normalizeList(res);

      // 前端排序
      if (this.data.sortType === 'sales') {
        list = list.sort((a, b) => Number(b.total_sales || b.sales || 0) - Number(a.total_sales || a.sales || 0));
      } else if (this.data.sortType === 'price') {
        list = list.sort((a, b) => {
          const pa = Number(a.sale_price || a.price || 0);
          const pb = Number(b.sale_price || b.price || 0);
          return this.data.sortPriceAsc ? pa - pb : pb - pa;
        });
      }

      this.setData({
        items: this.data.items.concat(list),
        page: page + 1,
        finished: list.length < pageSize,
      });
    } catch (e) {
      this.setData({ finished: true });
    } finally {
      this.setData({ loading: false });
    }
  },

  normalizeList(input) {
    if (Array.isArray(input)) return input;
    if (input && Array.isArray(input.data)) return input.data;
    if (input && Array.isArray(input.list)) return input.list;
    if (input && Array.isArray(input.items)) return input.items;
    return [];
  },

  onReachBottom() { this.loadMore(); },
});
