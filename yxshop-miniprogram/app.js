// app.js
const { getToken, getUser, clearAuth } = require('./utils/auth');
const config = require('./config/index');

App({
  globalData: {
    apiBase: config.apiBase,
    appId: config.appId,
    userInfo: null,
    systemInfo: null,
    privacyResolve: null,
    showPrivacyPopup: false,
    referrerId: 0,  // 推荐人ID（从分享链接/场景值提取）
  },

  onLaunch(options) {
    try {
      this.globalData.systemInfo = wx.getSystemInfoSync();
    } catch (e) { /* ignore */ }

    if (getToken()) {
      this.globalData.userInfo = getUser();
    }

    // 提取推荐人ID：从启动参数 query.ref 或场景值
    this._extractReferrerId(options);

    this.initPrivacy();
    this.checkUpdate();
  },

  initPrivacy() {
    if (!wx.canIUse || !wx.canIUse('onNeedPrivacyAuthorization')) return;
    wx.onNeedPrivacyAuthorization((resolve) => {
      this.globalData.privacyResolve = resolve;
      this.globalData.showPrivacyPopup = true;
    });
  },

  resolvePrivacyAuthorization(event) {
    if (this.globalData.privacyResolve) {
      this.globalData.privacyResolve({ event });
      this.globalData.privacyResolve = null;
    }
    this.globalData.showPrivacyPopup = false;
  },

  /**
   * 提取推荐人ID
   * 支持两种入口：
   *   1) 扫码进入小程序（options.scene = ref_123）
   *   2) 分享链接携带 query.ref = 123
   */
  _extractReferrerId(options) {
    if (!options) return;
    let ref = 0;

    // 方式1：从 query 参数提取
    if (options.query && options.query.ref) {
      ref = parseInt(options.query.ref, 10) || 0;
    }

    // 方式2：从场景值提取（扫码场景 scene=ref_123）
    if (!ref && options.scene) {
      const scene = decodeURIComponent(options.scene);
      const match = scene.match(/^ref[_=](\d+)$/i);
      if (match) {
        ref = parseInt(match[1], 10) || 0;
      }
    }

    if (ref > 0) {
      this.globalData.referrerId = ref;
      // 持久化到本地，登录时读取
      try { wx.setStorageSync('referrerId', ref); } catch (e) {}
    }
  },

  /**
   * 检查小程序更新
   */
  checkUpdate() {
    if (!wx.getUpdateManager) return;
    const manager = wx.getUpdateManager();
    manager.onUpdateReady(() => {
      wx.showModal({
        title: '更新提示',
        content: '新版本已下载，是否重启应用？',
        success: (res) => res.confirm && manager.applyUpdate(),
      });
    });
  },

  /**
   * 退出登录（在多处复用）
   */
  logout() {
    clearAuth();
    this.globalData.userInfo = null;
    wx.reLaunch({ url: '/pages/home/home' });
  },
});
