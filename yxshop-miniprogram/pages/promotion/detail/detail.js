/**
 * 促销活动详情
 * 对应管理端「营销管理 → 促销活动 / 商品促销」
 */
const api = require('../../../api/index');

Page({
  data: {
    id: '',
    detail: null,
    items: [],
    loading: true,
  },

  onLoad(options) {
    const id = options.id || '';
    this.setData({ id });
    this.loadDetail();
  },

  onShow() {
    // 从商品详情返回时刷新一次，保证库存/价格是最新的
    if (this.data.id && !this.data.loading) {
      this.loadDetail(true);
    }
  },

  loadDetail(silent) {
    if (!silent) this.setData({ loading: true });
    api.promotion
      .getDetail(this.data.id)
      .then((res) => {
        const detail = res.data || res || {};
        this.setData({
          detail,
          items: detail.prom_items || [],
          loading: false,
        });
      })
      .catch(() => {
        this.setData({ loading: false, detail: null, items: [] });
      });
  },

  /** 点击促销商品跳详情 */
  onTapItem(e) {
    const id = e.currentTarget.dataset.id;
    if (!id) return;
    wx.navigateTo({ url: '/pages/item-detail/item-detail?id=' + id });
  },
});
