const api = require('../../../api/index');
const imageUtil = require('../../../utils/image');

Page({
  data: {
    statusBarHeight: 20,
    currentTab: 'submit',
    feedbackTypes: [
      { value: 1, label: '建议', icon: 'gift' },
      { value: 2, label: '故障', icon: 'alert-circle' },
      { value: 3, label: '投诉', icon: 'flame' },
      { value: 4, label: '其他', icon: 'message' },
    ],
    form: {
      feedback_type: 1,
      content: '',
      contact: '',
      images: [],
    },
    submitting: false,
    history: [],
    loading: false,
    unreadReply: 0,
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
  },

  onShow() {
    if (this.data.currentTab === 'history') {
      this.loadHistory();
    }
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  onTabChange(e) {
    var tab = e.currentTarget.dataset.tab;
    this.setData({ currentTab: tab });
    if (tab === 'history' && !this.data.history.length) {
      this.loadHistory();
    }
  },

  onTypeSelect(e) {
    var value = e.currentTarget.dataset.value;
    this.setData({ 'form.feedback_type': value });
  },

  onContentInput(e) {
    this.setData({ 'form.content': e.detail.value });
  },

  onContactInput(e) {
    this.setData({ 'form.contact': e.detail.value });
  },

  onChooseImage() {
    var self = this;
    var current = this.data.form.images;
    wx.chooseMedia({
      count: 4 - current.length,
      mediaType: ['image'],
      sizeType: ['compressed'],
      success: async function (res) {
        var tempFiles = res.tempFiles || [];
        var uploaded = [];
        for (var i = 0; i < tempFiles.length; i++) {
          try {
            var result = await api.upload.uploadImage(tempFiles[i].tempFilePath, { scene: 'feedback' });
            var url = (result && (result.url || result.path)) || tempFiles[i].tempFilePath;
            uploaded.push(imageUtil.resolve(url));
          } catch (err) {
            uploaded.push(tempFiles[i].tempFilePath);
          }
        }
        self.setData({ 'form.images': current.concat(uploaded) });
      },
    });
  },

  onPreviewImage(e) {
    var index = e.currentTarget.dataset.index;
    var urls = this.data.form.images;
    wx.previewImage({ current: urls[index], urls: urls });
  },

  async onSubmit() {
    if (this.data.submitting) return;
    var content = this.data.form.content.trim();
    if (content.length < 10) {
      return wx.showToast({ title: '反馈内容至少10个字', icon: 'none' });
    }
    this.setData({ submitting: true });
    try {
      await api.feedback.submit({
        feedback_type: this.data.form.feedback_type,
        content: content,
        contact: this.data.form.contact,
        images: this.data.form.images.join(','),
      });
      wx.showToast({ title: '提交成功', icon: 'success' });
      this.setData({
        form: { feedback_type: 1, content: '', contact: '', images: [] },
        currentTab: 'history',
        history: [],
      });
      this.loadHistory();
    } catch (e) {
      // toast 已弹
    } finally {
      this.setData({ submitting: false });
    }
  },

  async loadHistory() {
    this.setData({ loading: true });
    try {
      var res = await api.feedback.myList({ page: 1, page_size: 50 });
      var list = [];
      if (res && res.list) {
        list = res.list;
      } else if (res && res.data) {
        list = res.data.data || res.data.list || [];
      } else if (Array.isArray(res)) {
        list = res;
      }

      var formatted = list.map(function (item) {
        var imgs = [];
        if (item.images) {
          imgs = item.images.split(',').filter(Boolean).map(function (url) {
            return imageUtil.resolve(url);
          });
        }
        var ts = item.created_at || 0;
        var date = ts ? new Date(ts * 1000) : new Date();
        var timeText = date.getFullYear() + '-' +
          String(date.getMonth() + 1).padStart(2, '0') + '-' +
          String(date.getDate()).padStart(2, '0') + ' ' +
          String(date.getHours()).padStart(2, '0') + ':' +
          String(date.getMinutes()).padStart(2, '0');

        return Object.assign({}, item, {
          imageList: imgs,
          time_text: timeText,
        });
      });

      var unread = formatted.filter(function (item) {
        return item.reply_content && item.status === 1;
      }).length;

      this.setData({ history: formatted, unreadReply: unread });
    } catch (e) {
      // ignore
    } finally {
      this.setData({ loading: false });
    }
  },

  onPreviewHistoryImage(e) {
    var src = e.currentTarget.dataset.src;
    var urls = e.currentTarget.dataset.urls;
    wx.previewImage({ current: src, urls: urls });
  },
});
