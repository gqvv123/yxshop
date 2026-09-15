const { get } = require('../../../utils/request');

Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    pointItemIds: [],
    items: [],
    loading: true,
  },
  observers: {
    props(val) {
      if (!val) return;
      this.setData({ pointItemIds: val.point_item_ids || [] });
    },
  },
  lifetimes: {
    attached() {
      this.loadData();
    },
  },
  methods: {
    async loadData() {
      const ids = this.data.pointItemIds;
      if (!ids || !ids.length) {
        this.setData({ loading: false });
        return;
      }
      this.setData({ loading: true });
      try {
        const res = await get('/api/v1/point-item/list', { ids: ids.join(',') }).catch(() => null);
        this.setData({ items: this.normalizeList(res) });
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
    onItemTap(e) {
      const id = e.currentTarget.dataset.id;
      if (!id) return;
      wx.navigateTo({ url: '/pages/item-detail/item-detail?id=' + id });
    },
    onCoverError(e) {
      var idx = e.currentTarget.dataset.index;
      if (idx === undefined) return;
      var items = this.data.items.slice();
      if (items[idx]) {
        items[idx].cover = '';
        items[idx].image = '';
        this.setData({ items: items });
      }
    },
  },
});