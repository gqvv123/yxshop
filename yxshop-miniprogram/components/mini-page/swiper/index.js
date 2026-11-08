Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    images: [],
    autoplay: true,
    interval: 3000,
    circular: true,
    indicatorDots: true,
  },
  observers: {
    props(val) {
      if (!val) return;
      // 过滤掉 URL 为空的图片，使占位 Banner 能正确展示
      var validImages = (val.images || []).filter(function (img) {
        return img && (img.image || img.url);
      });
      this.setData({
        images: validImages,
        autoplay: val.autoplay !== false,
        interval: val.interval || 3000,
        circular: val.circular !== false,
        indicatorDots: val.indicator_dots !== false,
      });
    },
  },
  methods: {
    onImageTap(e) {
      const idx = e.currentTarget.dataset.index;
      const img = this.data.images[idx] || {};
      const link = img.link || img.url;
      if (!link) return;
      if (/^\//.test(link)) {
        wx.navigateTo({ url: link });
      } else if (/^https?:/.test(link)) {
        wx.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(link) });
      }
    },
    onImageError(e) {
      // 图片加载失败时移除该图片，触发占位 Banner
      var idx = e.currentTarget.dataset.index;
      var images = this.data.images.slice();
      images.splice(idx, 1);
      this.setData({ images: images });
    },
    onPlaceholderTap() {
      wx.navigateTo({ url: '/pages/item-list/item-list?type=hot' });
    },
  },
});
