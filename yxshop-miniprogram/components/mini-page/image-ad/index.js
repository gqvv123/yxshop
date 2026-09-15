Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    layout: 'single',
    images: [],
  },
  observers: {
    props(val) {
      if (!val) return;
      this.setData({
        layout: val.layout || 'single',
        images: val.images || [],
      });
    },
  },
  methods: {
    onImageTap(e) {
      const idx = e.currentTarget.dataset.index;
      const link = this.data.images[idx] && this.data.images[idx].link;
      if (!link) return;
      if (/^\//.test(link)) {
        wx.navigateTo({ url: link });
      } else if (/^https?:/.test(link)) {
        wx.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(link) });
      }
    },
  },
});