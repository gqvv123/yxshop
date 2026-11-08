const api = require('../../../api/index');

Page({
  data: {
    statusBarHeight: 20,
    orderId: 0,
    order: null,
    rating: 5,
    content: '',
    images: [],
    isAnonymous: false,
    submitting: false,
  },

  onLoad(options) {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    const orderId = options.id || '';
    if (!orderId) {
      wx.showToast({ title: '订单不存在', icon: 'none' });
      return;
    }
    this.setData({ orderId });
    this.loadOrder();
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  async loadOrder() {
    try {
      const order = await api.order.getDetail(this.data.orderId);
      this.setData({ order });
    } catch (e) { /* toast 已弹 */ }
  },

  onRate(e) {
    this.setData({ rating: Number(e.currentTarget.dataset.score) });
  },

  onContentInput(e) {
    this.setData({ content: e.detail.value });
  },

  onToggleAnonymous() {
    this.setData({ isAnonymous: !this.data.isAnonymous });
  },

  onChooseImage() {
    const remaining = 9 - this.data.images.length;
    if (remaining <= 0) return;
    wx.chooseImage({
      count: remaining,
      sizeType: ['compressed'],
      sourceType: ['album', 'camera'],
      success: (res) => {
        this.setData({ images: [...this.data.images, ...res.tempFilePaths] });
      },
      fail: (err) => {
        if (err && err.errMsg && err.errMsg.indexOf('privacy') > -1) {
          wx.showToast({ title: '需要同意隐私协议才能选择图片', icon: 'none' });
        }
      },
    });
  },

  onRemoveImage(e) {
    const idx = Number(e.currentTarget.dataset.index);
    const images = [...this.data.images];
    images.splice(idx, 1);
    this.setData({ images });
  },

  async onSubmit() {
    const { orderId, rating, content, images, isAnonymous, submitting } = this.data;
    if (submitting) return;
    if (!content || content.length < 5) {
      return wx.showToast({ title: '评价内容至少5个字', icon: 'none' });
    }

    this.setData({ submitting: true });
    try {
      const uploadedImages = [];
      for (const img of images) {
        try {
          const r = await api.upload.uploadImage(img);
          uploadedImages.push(r.url || r.path || img);
        } catch (e) {
          uploadedImages.push(img);
        }
      }

      await api.order.review({
        id: orderId,
        rating,
        content,
        images: uploadedImages,
        is_anonymous: isAnonymous ? 1 : 0,
      });
      wx.showToast({ title: '评价成功', icon: 'success' });
      setTimeout(() => wx.navigateBack(), 800);
    } catch (e) {
      wx.showToast({ title: e.message || '评价失败', icon: 'none' });
    } finally {
      this.setData({ submitting: false });
    }
  },
});