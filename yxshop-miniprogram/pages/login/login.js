// pages/login/login.js
const api = require('../../api/index');
const auth = require('../../utils/auth');
const config = require('../../config/index');
const { loginWithWx, getReferrerId } = require('../../utils/wx-login');

Page({
  data: {
    mode: 'login', // login | register
    form: { username: '', password: '', phone: '', nickname: '' },
    submitting: false,
    wxSubmitting: false,
    showPassword: false,
    agreed: false,
    statusBarHeight: 20,
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
  },

  // ===== 表单交互 =====

  onInput(e) {
    const field = e.currentTarget.dataset.field;
    this.setData({ [`form.${field}`]: e.detail.value });
  },

  switchMode(e) {
    this.setData({ mode: e.currentTarget.dataset.mode });
  },

  onTogglePassword() {
    this.setData({ showPassword: !this.data.showPassword });
  },

  onToggleAgree() {
    this.setData({ agreed: !this.data.agreed });
  },

  onViewTerms() {
    wx.navigateTo({ url: '/pages/webview/webview?type=terms' });
  },

  onViewPrivacy() {
    wx.navigateTo({ url: '/pages/webview/webview?type=privacy' });
  },

  onBack() {
    const pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/home/home' });
    }
  },

  _checkAgreement() {
    if (!this.data.agreed) {
      wx.showToast({ title: '请先同意用户协议和隐私政策', icon: 'none' });
      return false;
    }
    return true;
  },

  // ===== 微信一键登录 =====

  async onWxLogin() {
    if (this.data.wxSubmitting) return;
    if (!this._checkAgreement()) return;

    this.setData({ wxSubmitting: true });
    try {
      await loginWithWx();
      wx.showToast({ title: '登录成功', icon: 'success' });
      this.navigateBackOrHome();
    } catch (e) {
      wx.showToast({ title: e.message || '微信登录失败', icon: 'none' });
    } finally {
      this.setData({ wxSubmitting: false });
    }
  },

  // ===== 账号登录/注册 =====

  async onSubmit() {
    if (this.data.submitting) return;
    if (!this._checkAgreement()) return;

    const { mode, form } = this.data;
    if (!form.username && !form.phone) {
      return wx.showToast({ title: '请填写账号', icon: 'none' });
    }
    if (mode === 'register' && !form.phone) {
      return wx.showToast({ title: '请填写手机号', icon: 'none' });
    }
    if (!form.password || form.password.length < 6) {
      return wx.showToast({ title: '密码至少 6 位', icon: 'none' });
    }

    this.setData({ submitting: true });
    try {
      const payload = {
        username: form.username || form.phone,
        password: form.password,
        phone: form.phone,
        nickname: form.nickname,
        app_id: config.appId,
        ref: getReferrerId() || undefined,
      };
      const res = mode === 'register'
        ? await api.auth.register(payload)
        : await api.auth.login(payload);

      auth.saveAuth(res);
      getApp().globalData.userInfo = res.user;

      wx.showToast({ title: mode === 'register' ? '注册成功' : '登录成功', icon: 'success' });
      this.navigateBackOrHome();
    } catch (e) {
      // toast 已弹
    } finally {
      this.setData({ submitting: false });
    }
  },

  navigateBackOrHome() {
    setTimeout(() => {
      const pages = getCurrentPages();
      if (pages.length > 1) {
        wx.navigateBack();
      } else {
        wx.switchTab({ url: '/pages/home/home' });
      }
    }, 600);
  },

  onForgotPassword() {
    wx.navigateTo({ url: '/pages/forgot-password/forgot-password' });
  },
});
