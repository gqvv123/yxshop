Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    couponIds: [],
    coupons: [],
    loading: true,
  },
  observers: {
    props(val) {
      if (!val) return;
      this.setData({ couponIds: val.coupon_ids || [] });
    },
  },
  lifetimes: {
    attached() {
      this.loadCoupons();
    },
  },
  methods: {
    async loadCoupons() {
      const ids = this.data.couponIds;
      if (!ids || !ids.length) {
        this.setData({ loading: false });
        return;
      }
      this.setData({ loading: true });
      try {
        const res = await this.fetchCoupons(ids);
        this.setData({ coupons: this.normalizeList(res) });
      } catch (e) {
        // ignore
      } finally {
        this.setData({ loading: false });
      }
    },
    fetchCoupons(ids) {
      const { get } = require('../../../utils/request');
      return get('/api/v1/coupon/list', { ids: ids.join(',') });
    },
    normalizeList(input) {
      if (Array.isArray(input)) return input;
      if (input && Array.isArray(input.data)) return input.data;
      if (input && Array.isArray(input.list)) return input.list;
      return [];
    },
    onCouponTap(e) {
      const id = e.currentTarget.dataset.id;
      if (!id) return;
      wx.navigateTo({ url: '/pages/item-list/item-list?coupon_id=' + id });
    },
  },
});