const api = require('../../../api/index');
const auth = require('../../../utils/auth');

Page({
  data: {
    statusBarHeight: 20,
    securityInfo: {
      score: 0,
      level: '低',
      level_color: '#dc2626',
      items: [],
      last_login_at: '',
      login_count: 0,
      last_login_ip: '',
      created_at: '',
    },
    scoreTip: '完善安全设置，提升账号安全性',
    iconMap: {
      password: 'lock',
      phone: 'smartphone',
      wechat: 'message',
      email: 'mail',
    },
    // 修改密码
    showPasswordModal: false,
    pwdForm: { old_password: '', new_password: '', confirm_password: '' },
    showOldPwd: false,
    showNewPwd: false,
    showConfirmPwd: false,
    saving: false,
    // 注销账号
    showDeactivateModal: false,
    deactivatePwd: '',
    showDeactPwd: false,
    deactivating: false,
  },

  onLoad() {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.loadSecurityInfo();
  },

  onShow() {
    this.loadSecurityInfo();
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  noop() {},

  async loadSecurityInfo() {
    try {
      var res = await api.security.getInfo();
      var info = (res && res.data) ? res.data : (res || {});
      var tip = '完善安全设置，提升账号安全性';
      if (info.score >= 80) {
        tip = '您的账号安全等级较高，请继续保持';
      } else if (info.score >= 60) {
        tip = '建议绑定手机和邮箱，进一步提升安全性';
      } else {
        tip = '您的账号存在安全风险，请尽快完善设置';
      }
      this.setData({ securityInfo: info, scoreTip: tip });
    } catch (e) {
      // ignore
    }
  },

  // ===== 安全项操作 =====
  onItemAction(e) {
    var key = e.currentTarget.dataset.key;
    if (key === 'password') {
      this.showPasswordModal();
    } else if (key === 'phone') {
      this.onPhoneAction();
    } else if (key === 'wechat') {
      this.onWechatAction();
    } else if (key === 'email') {
      this.onEmailAction();
    }
  },

  // 修改密码
  showPasswordModal() {
    this.setData({
      showPasswordModal: true,
      pwdForm: { old_password: '', new_password: '', confirm_password: '' },
      showOldPwd: false,
      showNewPwd: false,
      showConfirmPwd: false,
    });
  },

  hidePasswordModal() {
    this.setData({ showPasswordModal: false });
  },

  onPwdInput(e) {
    var field = e.currentTarget.dataset.field;
    this.setData({ ['pwdForm.' + field]: e.detail.value });
  },

  onTogglePwd(e) {
    var field = e.currentTarget.dataset.field;
    this.setData({ [field]: !this.data[field] });
  },

  async onSavePassword() {
    if (this.data.saving) return;
    var f = this.data.pwdForm;
    if (!f.old_password) {
      return wx.showToast({ title: '请输入原密码', icon: 'none' });
    }
    if (!f.new_password) {
      return wx.showToast({ title: '请输入新密码', icon: 'none' });
    }
    if (f.new_password.length < 6) {
      return wx.showToast({ title: '密码至少6位', icon: 'none' });
    }
    if (f.new_password !== f.confirm_password) {
      return wx.showToast({ title: '两次密码不一致', icon: 'none' });
    }
    this.setData({ saving: true });
    try {
      await api.security.changePassword({
        old_password: f.old_password,
        new_password: f.new_password,
      });
      wx.showToast({ title: '密码修改成功', icon: 'success' });
      this.setData({ showPasswordModal: false });
      this.loadSecurityInfo();
    } catch (e) {
      // toast 已弹
    } finally {
      this.setData({ saving: false });
    }
  },

  // 手机号操作
  onPhoneAction() {
    var items = this.data.securityInfo.items || [];
    var phoneItem = items.find(function (i) { return i.key === 'phone'; });
    if (phoneItem && phoneItem.bound) {
      wx.showModal({
        title: '更换手机号',
        content: '如需更换手机号，请联系客服处理',
        showCancel: false,
      });
    } else {
      wx.showModal({
        title: '绑定手机号',
        content: '请通过微信授权获取手机号进行绑定',
        confirmText: '去绑定',
        success: function (res) {
          if (res.confirm) {
            wx.showToast({ title: '请使用微信授权绑定', icon: 'none' });
          }
        },
      });
    }
  },

  // 微信操作
  onWechatAction() {
    var items = this.data.securityInfo.items || [];
    var wxItem = items.find(function (i) { return i.key === 'wechat'; });
    if (wxItem && wxItem.bound) {
      wx.showModal({
        title: '解绑微信',
        content: '解绑后将无法使用微信登录，确定解绑吗？',
        success: function (res) {
          if (res.confirm) {
            wx.showToast({ title: '请联系客服解绑', icon: 'none' });
          }
        },
      });
    } else {
      wx.showToast({ title: '请前往登录页绑定', icon: 'none' });
    }
  },

  // 邮箱操作
  onEmailAction() {
    var items = this.data.securityInfo.items || [];
    var emailItem = items.find(function (i) { return i.key === 'email'; });
    if (emailItem && emailItem.bound) {
      wx.showModal({
        title: '邮箱管理',
        content: '当前绑定邮箱：' + (emailItem.value || '') + '\n\n如需更换邮箱，请联系客服处理。',
        confirmText: '联系客服',
        confirmColor: '#2563eb',
        cancelText: '关闭',
        success: function (res) {
          if (res.confirm) {
            wx.makePhoneCall({
              phoneNumber: '4008888888',
              fail: function () {},
            });
          }
        },
      });
    } else {
      wx.showModal({
        title: '绑定邮箱',
        content: '绑定邮箱后可接收订单通知、安全提醒等重要消息。\n\n如需绑定邮箱，请联系客服协助处理。',
        confirmText: '联系客服',
        confirmColor: '#2563eb',
        cancelText: '稍后',
        success: function (res) {
          if (res.confirm) {
            wx.makePhoneCall({
              phoneNumber: '4008888888',
              fail: function () {},
            });
          }
        },
      });
    }
  },

  // ===== 注销账号 =====
  onShowDeactivate() {
    this.setData({
      showDeactivateModal: true,
      deactivatePwd: '',
      showDeactPwd: false,
    });
  },

  hideDeactivateModal() {
    this.setData({ showDeactivateModal: false });
  },

  onDeactPwdInput(e) {
    this.setData({ deactivatePwd: e.detail.value });
  },

  onConfirmDeactivate() {
    if (this.data.deactivating) return;
    var pwd = this.data.deactivatePwd;
    if (!pwd) {
      return wx.showToast({ title: '请输入密码', icon: 'none' });
    }
    var self = this;
    wx.showModal({
      title: '最终确认',
      content: '注销操作不可逆，确定要注销账号吗？',
      confirmText: '确认注销',
      confirmColor: '#dc2626',
      success: async function (res) {
        if (!res.confirm) return;
        self.setData({ deactivating: true });
        try {
          await api.security.deactivate({ password: pwd });
          wx.showToast({ title: '账号已注销', icon: 'none' });
          setTimeout(function () {
            auth.logout();
            wx.reLaunch({ url: '/pages/login/login' });
          }, 1500);
        } catch (e) {
          // toast 已弹
        } finally {
          self.setData({ deactivating: false });
        }
      },
    });
  },
});
