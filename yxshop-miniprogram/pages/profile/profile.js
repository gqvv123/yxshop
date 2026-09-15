// pages/profile/profile.js
const auth = require('../../utils/auth');
const api = require('../../api/index');
const imageUtil = require('../../utils/image');

Page({
  data: {
    user: null,
    userAvatar: '',
    isLoggedIn: false,
    unreadCount: 0,
    statusBarHeight: 20,
    /** 用户标签 */
    userTags: [],
    /** 统计数据 */
    stats: {
      favoriteCount: 0,
      followCount: 0,
      footprintCount: 0,
      reviewCount: 0,
      contractCount: 0,
    },
    /** 订单各状态数量 */
    orderCounts: {
      pending_payment: 0,
      pending_ship: 0,
      pending_receive: 0,
      pending_review: 0,
    },
    /** 钱包数据 */
    wallet: {
      balance: '0.00',
      points: 0,
      couponCount: 0,
      giftCount: 0,
    },
    /** 编辑面板 */
    showEdit: false,
    editForm: { nickname: '', avatar_url: '' },
    saving: false,
    languageLabel: '简体中文',
    darkModeLabel: '跟随系统',
    /** 版本号 */
    version: '1.0.0',
  },

  onLoad() {
    // 获取状态栏高度
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    // 读取本地设置
    this._loadSettings();
  },

  onShow() {
    const isLoggedIn = auth.isLoggedIn();
    this.setData({
      isLoggedIn,
      user: auth.getUser() || null,
    });
    if (isLoggedIn) {
      this._resolveAvatar();
      this._buildUserTags();
      this.loadUserInfo();
      this.loadUnreadCount();
      this.loadOrderCounts();
      this.loadWalletStats();
      this.loadFavoriteCount();
      this.loadFootprintCount();
    }
    // 同步自定义 tabbar 选中状态
    const tabBar = this.getTabBar && this.getTabBar();
    if (tabBar) tabBar.setSelected();
  },

  // ===== 数据加载 =====

  _resolveAvatar() {
    const u = this.data.user || {};
    this.setData({ userAvatar: u.avatar_url ? imageUtil.resolve(u.avatar_url) : '' });
  },

  _buildUserTags() {
    const u = this.data.user || {};
    const tags = [];
    if (u.is_verified || u.verified) {
      tags.push({ icon: 'shield', text: '企业认证' });
    }
    if (u.level >= 1 || u.is_vip) {
      tags.push({ icon: 'crown', text: 'VIP' + (u.level || 1) });
    }
    this.setData({ userTags: tags });
  },

  async loadUserInfo() {
    try {
      const u = await api.user.getInfo();
      auth.setUser(u);
      this.setData({ user: u });
      this._resolveAvatar();
      this._buildUserTags();
    } catch (e) { /* ignore */ }
  },

  async loadUnreadCount() {
    try {
      const r = await api.notification.unreadCount();
      this.setData({ unreadCount: (r && (r.count || r.unread)) || 0 });
    } catch (e) { /* ignore */ }
  },

  async loadOrderCounts() {
    try {
      const r = await api.order.statusCounts();
      if (!r) return;
      // 兼容多种返回结构
      const counts = {
        pending_payment: r.pending_payment || r.pendingPayment || 0,
        pending_ship: r.pending_ship || r.pendingShip || 0,
        pending_receive: r.pending_receive || r.pendingReceive || 0,
        pending_review: r.pending_review || r.pendingReview || 0,
      };
      this.setData({ orderCounts: counts, 'stats.reviewCount': counts.pending_review });
    } catch (e) { /* ignore */ }
  },

  async loadWalletStats() {
    try {
      // 优惠券统计
      const couponStats = await api.coupon.getStats().catch(() => null);
      let couponCount = 0;
      if (couponStats) {
        couponCount = couponStats.unused || couponStats.available || 0;
      }
      // 余额信息 — 优先使用 balance API
      let balance = '0.00';
      let points = 0;
      try {
        const bal = await api.balance.getBalance();
        if (bal) {
          balance = Number(bal.balance || 0).toFixed(2);
        }
      } catch (e) {
        // 降级：从用户信息取
        const u = this.data.user || {};
        balance = u.balance != null ? Number(u.balance).toFixed(2) : '0.00';
      }
      // 积分从用户信息取
      const userInfo = this.data.user || {};
      points = userInfo.points || 0;

      this.setData({
        wallet: {
          balance: balance,
          points: points,
          couponCount: couponCount,
          giftCount: 0,
        },
      });
    } catch (e) { /* ignore */ }
  },

  async loadFavoriteCount() {
    try {
      const r = await api.favorite.getList({ page: 1, page_size: 1 });
      const total = (r && (r.total || r.total_count)) || 0;
      this.setData({ 'stats.favoriteCount': total });
    } catch (e) { /* ignore */ }
  },

  async loadFootprintCount() {
    try {
      const r = await api.user.getFootprint({ page: 1, page_size: 1 });
      const total = (r && (r.total || r.total_count)) || 0;
      this.setData({ 'stats.footprintCount': total });
    } catch (e) { /* ignore */ }
  },


  // ===== 事件处理 =====

  onLogin() {
    wx.navigateTo({ url: '/pages/login/login' });
  },

  onLogout() {
    wx.showModal({
      title: '提示',
      content: '确定要退出登录吗？',
      success: async (res) => {
        if (!res.confirm) return;
        try {
          await api.auth.logout().catch(() => {});
        } finally {
          getApp().logout();
        }
      },
    });
  },

onMenu(e) {
const url = e.currentTarget.dataset.url;
if (!url) return;
if (!this.data.isLoggedIn) {
return this.onLogin();
}
// tabbar 页面需用 switchTab，且不能带参数
const tabPaths = ['/pages/home/home', '/pages/category/category', '/pages/cart/cart', '/pages/order/list/list', '/pages/profile/profile'];
const path = url.split('?')[0];
if (tabPaths.indexOf(path) >= 0) {
// 如果是订单页且带 status 参数，先存储再 switchTab
if (path === '/pages/order/list/list' && url.indexOf('?') >= 0) {
const match = url.match(/status=(\d+)/);
if (match) wx.setStorageSync('order_list_target_status', match[1]);
}
wx.switchTab({ url: path, fail: function () { wx.navigateTo({ url: url, fail: function () { wx.showToast({ title: '页面暂未开放', icon: 'none' }); } }); } });
} else {
wx.navigateTo({ url: url, fail: function (err) { wx.showToast({ title: '页面暂未开放', icon: 'none' }); } });
}
},

  onNotification() {
    if (!this.data.isLoggedIn) return this.onLogin();
    wx.navigateTo({ url: '/pages/notification/notification' });
  },

  onSettings() {
    if (!this.data.isLoggedIn) return this.onLogin();
    wx.navigateTo({ url: '/pages/profile/security/security' });
  },

  onCopyId() {
    const uid = (this.data.user && this.data.user.id) || '';
    if (!uid) return;
    wx.setClipboardData({
      data: String(uid),
      success: () => {
        wx.showToast({ title: '已复制', icon: 'none' });
      },
    });
  },

  onContactService() {
    wx.showModal({
      title: '联系客服',
      content: '客服热线：400-888-8888\n服务时间：周一至周日 9:00-22:00\n\n您也可以通过微信客服在线咨询。',
      confirmText: '在线咨询',
      confirmColor: '#2563eb',
      cancelText: '拨打热线',
      success: function (res) {
        if (res.confirm) {
          wx.openCustomerServiceChat({
            extInfo: { url: '' },
            corpId: '',
            fail: function () {
              wx.makePhoneCall({
                phoneNumber: '4008888888',
                fail: function () {
                  wx.showToast({ title: '请稍后重试', icon: 'none' });
                },
              });
            },
          });
        } else if (res.cancel) {
          wx.makePhoneCall({
            phoneNumber: '4008888888',
            fail: function () {},
          });
        }
      },
    });
  },


  // ===== 语言 / 深色模式 =====

  _loadSettings() {
    var lang = wx.getStorageSync('yxshop:language') || 'zh-CN';
    var dark = wx.getStorageSync('yxshop:darkMode') || 'system';
    var langMap = { 'zh-CN': '简体中文', 'zh-TW': '繁體中文', 'en': 'English' };
    var darkMap = { 'system': '跟随系统', 'light': '浅色模式', 'dark': '深色模式' };
    this.setData({
      languageLabel: langMap[lang] || '简体中文',
      darkModeLabel: darkMap[dark] || '跟随系统',
    });
  },

  onLanguageSetting() {
    var self = this;
    var current = wx.getStorageSync('yxshop:language') || 'zh-CN';
    wx.showActionSheet({
      itemList: ['简体中文', '繁體中文', 'English'],
      success: function (res) {
        var langMap = ['zh-CN', 'zh-TW', 'en'];
        var labelMap = ['简体中文', '繁體中文', 'English'];
        var lang = langMap[res.tapIndex] || 'zh-CN';
        wx.setStorageSync('yxshop:language', lang);
        self.setData({ languageLabel: labelMap[res.tapIndex] || '简体中文' });
        if (lang !== current) {
          wx.showToast({ title: '语言设置将在下次重启后生效', icon: 'none' });
        }
      },
    });
  },

  onDarkModeSetting() {
    var self = this;
    var current = wx.getStorageSync('yxshop:darkMode') || 'system';
    wx.showActionSheet({
      itemList: ['跟随系统', '浅色模式', '深色模式'],
      success: function (res) {
        var valMap = ['system', 'light', 'dark'];
        var labelMap = ['跟随系统', '浅色模式', '深色模式'];
        var val = valMap[res.tapIndex] || 'system';
        wx.setStorageSync('yxshop:darkMode', val);
        self.setData({ darkModeLabel: labelMap[res.tapIndex] || '跟随系统' });
        // 应用深色模式
        if (val === 'dark') {
          wx.showToast({ title: '深色模式将在下次重启后完全生效', icon: 'none' });
        } else if (val === 'light') {
          wx.showToast({ title: '已切换为浅色模式', icon: 'none' });
        } else {
          wx.showToast({ title: '已跟随系统设置', icon: 'none' });
        }
      },
    });
  },

  // ----- 编辑昵称头像 -----

  onEditProfile() {
    if (!this.data.isLoggedIn) return this.onLogin();
    const u = this.data.user || {};
    this.setData({
      showEdit: true,
      editForm: {
        nickname: u.nickname || '',
        avatar_url: u.avatar_url || '',
      },
    });
  },

  hideEdit() { this.setData({ showEdit: false }); },

  async onChooseAvatar(e) {
    const tmpUrl = e.detail.avatarUrl;
    if (!tmpUrl) return;
    try {
      const res = await api.upload.uploadImage(tmpUrl, { scene: 'avatar' });
      const url = (res && (res.url || res.path)) || tmpUrl;
      this.setData({ 'editForm.avatar_url': url });
    } catch (err) {
      this.setData({ 'editForm.avatar_url': tmpUrl });
    }
  },

  onNicknameInput(e) {
    this.setData({ 'editForm.nickname': e.detail.value });
  },

  async onSaveProfile() {
    if (this.data.saving) return;
    const { nickname, avatar_url } = this.data.editForm;
    if (!nickname && !avatar_url) {
      return wx.showToast({ title: '请填写昵称或选择头像', icon: 'none' });
    }
    this.setData({ saving: true });
    try {
      const data = await api.auth.updateWxProfile({ nickname, avatar_url });
      auth.setUser(data);
      this.setData({ user: data, showEdit: false });
      this._resolveAvatar();
      wx.showToast({ title: '保存成功', icon: 'success' });
    } catch (e) {
      // toast 已弹
    } finally {
      this.setData({ saving: false });
    }
  },

  /**
   * 微信手机号一键绑定
   */
  async onGetPhoneNumber(e) {
    if (!this.data.isLoggedIn) return this.onLogin();
    const d = e.detail || {};
    if (!d.code && !d.encryptedData) {
      return wx.showToast({ title: '已取消', icon: 'none' });
    }
    const payload = d.code
      ? { code: d.code }
      : { encrypted_data: d.encryptedData, iv: d.iv };
    try {
      const res = await api.auth.bindPhone(payload);
      if (res && res.user) {
        auth.setUser(res.user);
        this.setData({ user: res.user });
      }
      wx.showToast({ title: '绑定成功', icon: 'success' });
    } catch (err) {
      // toast 已弹
    }
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
