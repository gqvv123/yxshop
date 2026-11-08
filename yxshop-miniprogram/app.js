// app.js
const { getToken, getUser, clearAuth } = require('./utils/auth');
const config = require('./config/index');

App({
  globalData: {
    apiBase: config.apiBase,
    appId: config.appId,
    userInfo: null,
    privacyResolve: null,
    showPrivacyPopup: false,
    referrerId: 0,  // 推荐人ID（从分享链接/场景值提取）
  },

  onLaunch(options) {
    if (getToken()) {
      this.globalData.userInfo = getUser();
    }

    // 提取推荐人ID：从启动参数 query.ref 或场景值
    this._extractReferrerId(options);

    this.initPrivacy();
    this.checkUpdate();
    this.checkBusinessVersion();
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
   * 业务版本检查（对应管理端「应用版本管理」）
   *
   * 与 checkUpdate 的区别：
   *   checkUpdate         → 微信小程序包本身的更新（getUpdateManager）
   *   checkBusinessVersion→ 后端登记的版本策略：有新版本弹提示，强制更新时不可取消
   *
   * 失败静默：版本服务不可用绝不能阻断用户使用。
   */
  checkBusinessVersion() {
    const { get } = require('./utils/request');
    get('/api/v1/version/check-update', {
      platform: 'miniprogram',
      current_version_code: config.versionCode || 1,
      channel: 'official',
    }, { silent: true, showError: false })
      .then((res) => {
        if (!res || !res.has_update || !res.version) return;

        const force = !!res.force;
        const note = res.version.release_note || '';
        wx.showModal({
          title: force ? '需要更新' : '发现新版本',
          content: (note || `新版本 ${res.version.version_name || ''} 已发布，建议更新后使用。`),
          showCancel: !force,
          confirmText: '立即更新',
          cancelText: '稍后',
          success: (r) => {
            if (!r.confirm) return;
            // 小程序包内没有自更新能力，提示用户重启获取最新版本
            wx.showToast({ title: '请关闭后重新打开小程序', icon: 'none', duration: 2500 });
          },
        });
      })
      .catch(() => {
        // 版本服务异常不影响主流程
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
