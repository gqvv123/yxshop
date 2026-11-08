/**
 * 促销活动列表
 * 对应管理端「营销管理 → 促销活动」
 */
const api = require('../../../api/index');

Page({
  data: {
    list: [],
    page: 1,
    pageSize: 10,
    hasMore: true,
    loading: false,
    type: '',
    types: [
      { key: '', label: '全部' },
      { key: 'seckill', label: '秒杀' },
      { key: 'group', label: '拼团' },
      { key: 'discount', label: '限时折扣' },
    ],
  },

  onLoad() {
    this.loadList(true);
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadList(false);
    }
  },

  /** 切换活动类型 */
  onTypeChange(e) {
    const type = e.currentTarget.dataset.type || '';
    if (type === this.data.type) return;
    this.setData({ type, page: 1, list: [], hasMore: true });
    this.loadList(true);
  },

  /** 加载活动列表 */
  loadList(reset) {
    const page = reset ? 1 : this.data.page;
    this.setData({ loading: true });

    api.promotion
      .getList({ page, page_size: this.data.pageSize, type: this.data.type })
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

  /** 进入活动详情 */
  onTapItem(e) {
    const id = e.currentTarget.dataset.id;
    if (!id) return;
    wx.navigateTo({ url: '/pages/promotion/detail/detail?id=' + id });
  },
});
