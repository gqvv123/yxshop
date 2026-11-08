/**
 * 会员等级中心
 * 对应管理端「用户管理 → 用户等级」
 * 数据源：GET /api/v1/user/level（当前等级 / 下一等级 / 升级进度 / 等级体系 / 专属权益）
 */
const api = require('../../api/index');
const auth = require('../../utils/auth');
const { fmtPrice } = require('../../utils/format');

Page({
  data: {
    statusBarHeight: 20,
    loading: true,
    loadError: '',

    currentLevel: null,
    nextLevel: null,
    progress: 0,
    userExp: '0.00',
    needExp: '0.00',

    levels: [],
    benefits: [],
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    if (!auth.isLoggedIn()) {
      wx.redirectTo({ url: '/pages/login/login' });
      return;
    }
    this.load();
  },

  onPullDownRefresh() {
    this.load().then(() => wx.stopPullDownRefresh());
  },

  async load() {
    this.setData({ loading: true, loadError: '' });
    try {
      const d = (await api.user.getLevel()) || {};

      const levels = (d.all_levels || []).map((lv) => ({
        id: lv.id,
        name: lv.name,
        level: lv.level,
        experience: Number(lv.experience || 0),
        experienceText: fmtPrice(lv.experience || 0),
        discount: Number(lv.discount || 10),
        discountText: Number(lv.discount || 10) >= 10 ? '无折扣' : `${Number(lv.discount)} 折`,
        description: lv.description || '',
        isCurrent: !!lv.is_current,
      }));

      this.setData({
        currentLevel: d.current_level || null,
        nextLevel: d.next_level || null,
        progress: Math.min(100, Math.max(0, Number(d.progress || 0))),
        userExp: fmtPrice(d.user_exp || 0),
        needExp: fmtPrice(d.need_exp || 0),
        levels,
        benefits: d.benefits || [],
        loading: false,
      });
    } catch (e) {
      this.setData({
        loading: false,
        loadError: (e && e.message) || '加载失败',
      });
    }
  },

  /** 去逛逛（等级靠累计消费提升） */
  onGoShopping() {
    wx.switchTab({ url: '/pages/home/home' });
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },
});
