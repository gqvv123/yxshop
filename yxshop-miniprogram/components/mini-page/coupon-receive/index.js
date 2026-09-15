const { get, post } = require('../../../utils/request');

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
        const res = await get('/api/v1/coupon/list', { ids: ids.join(',') }).catch(() => null);
        this.setData({ coupons: this.normalizeList(res) });
      } catch (e) {
        // ignore
      } finally {
        this.setData({ loading: false });
      }
    },
    normalizeList(input) {
      if (Array.isArray(input)) return input;
      if (input && Array.isArray(input.data)) return input.data;
      if (input && Array.isArray(input.list)) return input.list;
      return [];
    },
    async onReceiveTap(e) {
      const id = e.currentTarget.dataset.id;
      if (!id) return;
      try {
        await post('/api/v1/coupon/receive', { coupon_id: id });
        wx.showToast({ title: '领取成功', icon: 'success' });
        this.loadCoupons();
      } catch (e) {
        // request.js already shows error toast
      }
    },
  },
});