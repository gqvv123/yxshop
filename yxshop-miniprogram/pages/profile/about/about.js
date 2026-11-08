Page({
  data: {
    statusBarHeight: 20,
    appName: '盈信企采',
    version: '1.0.0',
    year: new Date().getFullYear(),
    hotline: '400-888-8888',
    email: 'business@yxshop.com',
    address: '北京市朝阳区科技园区88号',
    workTime: '周一至周五 9:00-18:00',
    privacyPolicyUrl: '',
    userAgreementUrl: '',
    features: [
      { icon: 'truck', title: '一站式采购', desc: '从寻源到交付，全流程数字化管理' },
      { icon: 'shield', title: '品质保障', desc: '严格供应商准入，正品保证' },
      { icon: 'trending-up', title: '价格透明', desc: '实时比价，企业专享阶梯价' },
      { icon: 'headphones', title: '专属客服', desc: '一对一企业客户经理服务' },
    ],
    certifications: [
      { icon: 'shield', color: '#2563eb', title: 'ISO9001认证' },
      { icon: 'check-circle', color: '#047857', title: '高新技术企业' },
      { icon: 'gem', color: '#b45309', title: '诚信经营' },
      { icon: 'lock', color: '#6b21a8', title: '数据安全' },
    ],
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  onCall(e) {
    var phone = e.currentTarget.dataset.phone;
    if (phone) {
      wx.makePhoneCall({ phoneNumber: phone.replace(/-/g, '') });
    }
  },

  onCopy(e) {
    var text = e.currentTarget.dataset.text;
    if (!text) return;
    wx.setClipboardData({
      data: text,
      success: function () {
        wx.showToast({ title: '已复制', icon: 'none' });
      },
    });
  },

  onMenu(e) {
    var url = e.currentTarget.dataset.url;
    if (!url) return;
    wx.navigateTo({
      url: url,
      fail: function () {},
    });
  },

  onCopyLink() {
    wx.setClipboardData({
      data: 'https://www.yxshop.cn',
      success: function () {
        wx.showToast({ title: '链接已复制', icon: 'none' });
      },
    });
  },
});
