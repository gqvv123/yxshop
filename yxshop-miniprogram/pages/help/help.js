/**
 * 帮助中心 / 商城公告
 * 对应管理端「内容管理 → 文章管理 / 文章分类」
 */
const api = require('../../api/index');

Page({
  data: {
    categories: [],
    activeCat: 0,
    list: [],
    keyword: '',
    page: 1,
    pageSize: 10,
    hasMore: true,
    loading: false,
    detail: null,
    showDetail: false,
  },

  onLoad() {
    this.loadCategories();
    this.loadList(true);
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading && !this.data.showDetail) {
      this.loadList(false);
    }
  },

  /** 加载分类（帮助中心导航） */
  loadCategories() {
    api.article
      .getCategories()
      .then((res) => {
        const data = res.data || res || {};
        this.setData({ categories: data.list || [] });
      })
      .catch(() => {});
  },

  /** 切换分类 */
  onCatChange(e) {
    const idx = e.currentTarget.dataset.idx;
    const cat = this.data.categories[idx];
    this.setData({ activeCat: idx, category_id: cat ? cat.id : 0, page: 1, list: [], hasMore: true });
    this.loadList(true);
  },

  /** 搜索 */
  onSearch(e) {
    const keyword = (e.detail && e.detail.value) || '';
    this.setData({ keyword, page: 1, list: [], hasMore: true });
    this.loadList(true);
  },

  /** 加载文章列表 */
  loadList(reset) {
    const page = reset ? 1 : this.data.page;
    this.setData({ loading: true });

    api.article
      .getList({
        page,
        page_size: this.data.pageSize,
        keyword: this.data.keyword,
        category_id: this.data.category_id || 0,
      })
      .then((res) => {
        const data = res.data || res || {};
        const list = data.list || [];
        const merged = reset ? list : this.data.list.concat(list);
        this.setData({
          list: merged,
          page: page + 1,
          hasMore: list.length >= this.data.pageSize,
          loading: false,
        });
      })
      .catch(() => {
        this.setData({ loading: false, hasMore: false });
      });
  },

  /** 打开文章详情 */
  onTapArticle(e) {
    const id = e.currentTarget.dataset.id;
    if (!id) return;
    api.article
      .getDetail(id)
      .then((res) => {
        this.setData({ detail: res.data || res || null, showDetail: true });
        wx.setNavigationBarTitle({ title: (res.data && res.data.title) || '文章详情' });
      })
      .catch(() => {});
  },

  /** 关闭详情 */
  onCloseDetail() {
    this.setData({ showDetail: false, detail: null });
    wx.setNavigationBarTitle({ title: '帮助中心' });
  },
});
