Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    columns: 4,
    items: [],
    iconColors: [
      '#1e40af', '#be185d', '#047857', '#b45309',
      '#0369a1', '#6b21a8', '#b91c1c', '#0f766e',
    ],
  },
  observers: {
    props(val) {
      if (!val) return;
      var items = val.items || [];
      var columns = val.columns || 0;
      if (!columns && items.length) {
        columns = Math.min(items.length, 4);
        if (items.length > 4 && items.length <= 8) columns = 4;
        if (items.length > 8) columns = 4;
      }
      this.setData({
        columns: columns || 4,
        items: items,
      });
    },
  },
  methods: {
    onItemTap(e) {
      const idx = e.currentTarget.dataset.index;
      const link = this.data.items[idx] && this.data.items[idx].link;
      if (!link) return;
      if (/^\//.test(link)) {
        wx.navigateTo({ url: link });
      } else if (/^https?:/.test(link)) {
        wx.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(link) });
      }
    },
  },
});
