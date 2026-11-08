const api = require('../../api/index');

Page({
  data: {
    statusBarHeight: 20,
    step: 1,
    phone: '',
    code: '',
    password: '',
    confirmPassword: '',
    countdown: 0,
    submitting: false,
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
      wx.redirectTo({ url: '/pages/login/login' });
    }
  },

  onInput(e) {
    const field = e.currentTarget.dataset.field;
    this.setData({ [field]: e.detail.value });
  },

  async onSendCode() {
    const { phone, countdown } = this.data;
    if (countdown > 0) return;
    if (!phone || !/^1\d{10}$/.test(phone)) {
      return wx.showToast({ title: '请输入正确手机号', icon: 'none' });
    }
    try {
      await api.auth.forgotPassword({ phone });
      wx.showToast({ title: '验证码已发送', icon: 'success' });
      this.startCountdown();
    } catch (e) { /* toast 已弹 */ }
  },

  startCountdown() {
    this.setData({ countdown: 60 });
    this._timer = setInterval(() => {
      const c = this.data.countdown - 1;
      if (c <= 0) {
        clearInterval(this._timer);
        this.setData({ countdown: 0 });
      } else {
        this.setData({ countdown: c });
      }
    }, 1000);
  },

  onUnload() {
    if (this._timer) clearInterval(this._timer);
  },

  async onSubmit() {
    const { phone, code, password, confirmPassword, step, submitting } = this.data;
    if (submitting) return;

    if (step === 1) {
      if (!code) return wx.showToast({ title: '请输入验证码', icon: 'none' });
      this.setData({ step: 2 });
      return;
    }

    if (!password || password.length < 6) {
      return wx.showToast({ title: '密码至少6位', icon: 'none' });
    }
    if (password !== confirmPassword) {
      return wx.showToast({ title: '两次密码不一致', icon: 'none' });
    }

    this.setData({ submitting: true });
    try {
      await api.auth.resetPassword({ phone, code, password });
      wx.showToast({ title: '密码重置成功', icon: 'success' });
      setTimeout(() => wx.redirectTo({ url: '/pages/login/login' }), 800);
    } catch (e) { /* toast 已弹 */ } finally {
      this.setData({ submitting: false });
    }
  },
});