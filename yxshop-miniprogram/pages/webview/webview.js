// pages/webview/webview.js
Page({
  data: { url: '' },
  onLoad(options) {
    if (!options.url) {
      wx.showToast({ title: '链接不存在', icon: 'none' });
      return setTimeout(() => wx.navigateBack(), 800);
    }
    this.setData({ url: decodeURIComponent(options.url) });
  },
});
