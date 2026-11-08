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
    tags: [], // 商品标签（对应管理端「商品标签」）
    activeTag: 0, // 选中的标签 id，0=全部
    brandId: 0, // 品牌筛选（对应管理端「商品品牌」）
    brandName: '',
  },

  onLoad(options) {
    const type = options.type || '';
    const keyword = options.keyword || '';
    const brandId = Number(options.brand_id || 0);
    const map = { hot: '热销爆款', new: '上新好物', recommend: '为你推荐' };
    const title = brandId
      ? (options.brand_name ? decodeURIComponent(options.brand_name) : '品牌商品')
      : (keyword ? `搜索：${keyword}` : (map[type] || '商品列表'));
    this.setData({
      type,
      keyword,
      brandId,
      brandName: brandId ? title : '',
      title,
    });
    wx.setNavigationBarTitle({ title });
    this.loadTags();
    this.refresh();
  },

  async loadTags() {
    try {
      const res = await api.item.getTags();
      const list = Array.isArray(res)
        ? res
        : (res && Array.isArray(res.list) ? res.list : (res && Array.isArray(res.data) ? res.data : []));
      this.setData({
        tags: list
          .map((t) => ({
            id: t.id,
            name: t.tag_name || t.name || '',
          }))
          .filter((t) => t.name !== ''),
      });
    } catch (e) {
      // 标签接口不可用时静默降级，不影响主列表
      this.setData({ tags: [] });
    }
  },

  onTagSelect(e) {
    const id = Number(e.currentTarget.dataset.id || 0);
    if (id === this.data.activeTag) return;
    this.setData({ activeTag: id });
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

      // 注意：单租户商城 app_id 合法值就是 0，不能用 `|| 10001` 兜底
      // （0 是 falsy，会被改成 10001，导致商品 app_id=0 时永远查不到数据）
      const g = getApp();
      const appId = (g && g.globalData && typeof g.globalData.appId === 'number')
        ? g.globalData.appId
        : 0;

      // 排序交给服务端，避免只对当前页排序导致跨页顺序错乱
      const sortParams = this.data.sortType === 'price'
        ? { sort: 'price', order: this.data.sortPriceAsc ? 'asc' : 'desc' }
        : (this.data.sortType === 'sales' ? { sort: 'sales' } : {});

      if (this.data.type === 'hot') {
        res = await api.item.getHot({ page, limit: pageSize, app_id: appId });
      } else if (this.data.type === 'new') {
        res = await api.item.getNew({ page, limit: pageSize, app_id: appId });
      } else if (this.data.type === 'recommend') {
        res = await api.item.getRecommended({ page, limit: pageSize, app_id: appId });
      } else {
        res = await api.item.getList({
          page,
          page_size: pageSize,
          keyword: this.data.keyword,
          app_id: appId,
          tag_id: this.data.activeTag || undefined,
          brand_id: this.data.brandId || undefined,
          ...sortParams,
        });
      }

      const list = this.normalizeList(res);

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
