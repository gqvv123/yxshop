const api = require('../../../api/index');

Page({
  data: {
    statusBarHeight: 20,
    currentTab: 'apply',
    hotline: '400-888-8888',
    advantages: [
      { icon: 'trending-up', title: '海量企业客户', desc: '共享平台十万+企业采购客户资源' },
      { icon: 'truck', title: '一站式物流', desc: '覆盖全国仓储配送，降低履约成本' },
      { icon: 'shield', title: '资金安全保障', desc: '平台担保交易，账期灵活结算' },
      { icon: 'gem', title: '品牌赋能', desc: '平台背书提升品牌信任度与曝光' },
    ],
    modes: [
      { icon: 'briefcase', color: '#2563eb', title: '供应商入驻', desc: '提供商品，平台代运营销售' },
      { icon: 'home', color: '#7c3aed', title: '商家自营', desc: '独立店铺，自主运营管理' },
      { icon: 'users', color: '#047857', title: '分销合作', desc: '渠道分销，佣金灵活分润' },
      { icon: 'layers', color: '#b45309', title: '战略合伙', desc: '深度合作，资源互换共享' },
    ],
    steps: [
      { step: 1, title: '提交意向', desc: '填写合作申请表，提交基本信息' },
      { step: 2, title: '资质审核', desc: '平台1-3个工作日内审核资质' },
      { step: 3, title: '签订协议', desc: '审核通过后签订合作协议' },
      { step: 4, title: '上线运营', desc: '开通店铺/供应商后台，开始运营' },
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

  onTabChange(e) {
    var tab = e.currentTarget.dataset.tab;
    this.setData({ currentTab: tab });
    if (tab === 'status') {
      wx.showModal({
        title: '提示',
        content: '如需查询合作申请进度，请拨打招商热线咨询。',
        confirmText: '拨打热线',
        confirmColor: '#2563eb',
        success: (res) => {
          if (res.confirm) {
            this.onCallHotline();
          }
          this.setData({ currentTab: 'apply' });
        },
      });
    }
  },

  onSubmit() {
    wx.showModal({
      title: '提交合作意向',
      content: '感谢您的关注！请直接拨打招商热线 ' + this.data.hotline + ' 联系我们的商务团队，我们将为您提供一对一服务。',
      confirmText: '立即拨打',
      confirmColor: '#2563eb',
      cancelText: '稍后',
      success: (res) => {
        if (res.confirm) {
          this.onCallHotline();
        }
      },
    });
  },

  onCallHotline() {
    wx.makePhoneCall({ phoneNumber: this.data.hotline.replace(/-/g, '') });
  },
});
