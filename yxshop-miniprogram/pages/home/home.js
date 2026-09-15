const api = require('../../api/index');
const storage = require('../../utils/storage');
const registry = require('../../components/mini-page/registry');

const CACHE_KEY = 'mini_page_home';
const CACHE_TTL = 300;

Page({
  data: {
    components: [],
    heroComponents: [],
    bodyComponents: [],
    theme: {},
    tabbar: null,
    loading: true,
    isFallback: false,
    showPrivacy: false,
    statusBarHeight: 20,
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.loadPageData();
  },

  onShow() {
    this.checkPrivacy();
    this.syncTabBar();
  },

  checkPrivacy() {
    const app = getApp();
    if (app.globalData.showPrivacyPopup) {
      this.setData({ showPrivacy: true });
    }
  },

  onPrivacyAgree() {
    getApp().resolvePrivacyAuthorization('agree');
    this.setData({ showPrivacy: false });
  },

  onPrivacyDisagree() {
    getApp().resolvePrivacyAuthorization('disagree');
    this.setData({ showPrivacy: false });
  },

  onPullDownRefresh() {
    this.loadPageData().finally(() => wx.stopPullDownRefresh());
  },

  async loadPageData() {
    this.setData({ loading: true, isFallback: false });

    try {
      const res = await api.miniPage.getHomePage({ app_id: getApp().globalData.appId || 1 });
      const pageData = (res && res.page && res.page.page_data) || [];
      const theme = (res && res.theme) || {};
      const tabbar = (res && res.tabbar) || null;

      const sorted = (Array.isArray(pageData) ? pageData : [])
        .filter((c) => c && c.is_visible !== false && registry.componentMap[c.component_type])
        .map((c) => Object.assign({}, c, { props: (c.props && typeof c.props === 'object') ? c.props : {} }))
        .sort((a, b) => (a.sort || 0) - (b.sort || 0));

      const { heroComponents, bodyComponents } = this.splitHeroBody(sorted, theme);
      this.setData({ components: sorted, heroComponents, bodyComponents, theme, tabbar, loading: false, isFallback: false });
      this.applyTheme(theme);
      this.syncTabBar();
      this.cacheData(res);
    } catch (e) {
      const cached = this.getCachedData();
      if (cached) {
        const pageData = (cached.page && cached.page.page_data) || [];
        const sorted = (Array.isArray(pageData) ? pageData : [])
          .filter((c) => c && c.is_visible !== false && registry.componentMap[c.component_type])
          .map((c) => Object.assign({}, c, { props: (c.props && typeof c.props === 'object') ? c.props : {} }))
          .sort((a, b) => (a.sort || 0) - (b.sort || 0));
        const { heroComponents, bodyComponents } = this.splitHeroBody(sorted, cached.theme || {});
        this.setData({ components: sorted, heroComponents, bodyComponents, theme: cached.theme || {}, tabbar: cached.tabbar || null, loading: false, isFallback: true });
        this.applyTheme(cached.theme || {});
        this.syncTabBar();
      } else {
        const fbComponents = this.getFallbackComponents();
        const { heroComponents, bodyComponents } = this.splitHeroBody(fbComponents, this.getFallbackTheme());
        this.setData({ components: fbComponents, heroComponents, bodyComponents, theme: this.getFallbackTheme(), tabbar: null, loading: false, isFallback: true });
        this.applyTheme(this.getFallbackTheme());
        this.syncTabBar();
      }
    }
  },

  applyTheme(theme) {
    if (!theme) return;
    if (theme.primary_color) {
      try {
        wx.setNavigationBarColor({
          frontColor: this.resolveFrontColor(theme.nav_text_color),
          backgroundColor: theme.nav_background_color || theme.primary_color,
          animation: { duration: 300, timingFunc: 'easeIn' },
        });
      } catch (e) {
        // ignore
      }
    }
  },

  resolveFrontColor(navTextColor) {
    if (navTextColor === 'light') return '#ffffff';
    if (navTextColor === 'dark') return '#000000';
    if (typeof navTextColor === 'string' && /^#[0-9A-Fa-f]{6}$/.test(navTextColor)) {
      const r = parseInt(navTextColor.slice(1, 3), 16);
      const g = parseInt(navTextColor.slice(3, 5), 16);
      const b = parseInt(navTextColor.slice(5, 7), 16);
      const luminance = (0.299 * r + 0.587 * g + 0.114 * b) / 255;
      return luminance > 0.5 ? '#ffffff' : '#000000';
    }
    return '#ffffff';
  },

  /**
   * 同步自定义 tabbar：更新选中状态 + 推送最新配置
   * 自定义 tabbar（custom: true）不能用 wx.setTabBarStyle/setTabBarItem，
   * 必须通过 this.getTabBar() 直接操作组件实例。
   */
  syncTabBar() {
    const tabBar = this.getTabBar && this.getTabBar();
    if (!tabBar) return;
    if (this.data.tabbar && this.data.tabbar.items) {
      tabBar.updateConfig(this.data.tabbar);
    } else {
      tabBar.setSelected();
    }
  },

  /**
   * @deprecated 已由 syncTabBar 替代，保留空函数避免外部调用报错
   */
  applyTabBar(tabbar) {
    // 自定义 tabbar 模式下，原生 API 无效，
    // 配置通过 syncTabBar → this.getTabBar().updateConfig() 传递
  },

  cacheData(data) {
    if (!data) return;
    storage.set(CACHE_KEY, { data, ts: Math.floor(Date.now() / 1000) });
  },

  getCachedData() {
    const cached = storage.get(CACHE_KEY);
    if (!cached || !cached.data) return null;
    const now = Math.floor(Date.now() / 1000);
    if (now - cached.ts > CACHE_TTL) return null;
    return cached.data;
  },

  getFallbackComponents() {
    return [
      { component_type: 'search_bar', component_id: 'fb_search', sort: 1, is_visible: true, props: { placeholder: '搜索商品、品牌、品类…', style: 'round', show_scan: true, show_msg: true, msg_dot: false, show_city: true, city_name: '上海·浦东' } },
      { component_type: 'notice_bar', component_id: 'fb_notice', sort: 2, is_visible: true, props: { content: '欢迎光临企业采购平台，新人注册即领 200 元大礼包', scrollable: true, icon: 'volume', text_color: '#ffffff', background_color: 'rgba(255,255,255,0.12)' } },
      { component_type: 'promo_banner', component_id: 'fb_vip', sort: 3, is_visible: true, props: { style: 'glass', bg_color1: '#1e3a8a', bg_color2: '#2563eb', icon: 'crown', title: '企业会员 PRO', subtitle: '尊享专属价 · 月结 · 专属客服', btn_text: '立即开通 ›', btn_link: '/pages/profile/profile', show_pulse: false } },
      { component_type: 'grid_nav', component_id: 'fb_nav', sort: 4, is_visible: true, props: { columns: 5, items: [
        { text: '数码电器', icon_name: 'smartphone', icon: '', link: '/pages/category/category?id=1', badge: 'HOT' },
        { text: '服饰鞋包', icon_name: 'shirt', icon: '', link: '/pages/category/category?id=2', badge: '' },
        { text: '家居家装', icon_name: 'home', icon: '', link: '/pages/category/category?id=3', badge: '' },
        { text: '食品生鲜', icon_name: 'utensils', icon: '', link: '/pages/category/category?id=4', badge: '' },
        { text: '个护健康', icon_name: 'heart-pulse', icon: '', link: '/pages/category/category?id=5', badge: '' },
      ] } },
      { component_type: 'swiper', component_id: 'fb_swiper', sort: 5, is_visible: true, props: { images: [], autoplay: true, interval: 3000, circular: true, indicator_dots: true } },
      { component_type: 'product_list', component_id: 'fb_hot', sort: 6, is_visible: true, props: { source_type: 'hot', display_count: 4, sort_by: 'sales', columns: 2, title: '热门商品', icon: 'flame', show_more: true, more_link: '/pages/item-list/item-list?sort=sales', show_cart: true } },
    ];
  },

  getFallbackTheme() {
    return { primary_color: '#2563eb', secondary_color: '#3b82f6', nav_background_color: '#1e3a8a', nav_text_color: 'light' };
  },

  /**
   * 将组件拆分为 hero 区（蓝色渐变背景）和 body 区（白色内容区）
   * 规则：从第一个组件开始，连续的 search_bar / notice_bar / promo_banner(glass) 归入 hero 区
   * 遇到其他类型组件或 promo_banner(非glass) 时停止，之后全部归入 body 区
   */
  splitHeroBody(components, theme) {
    const heroComponents = [];
    const bodyComponents = [];
    let heroEnded = false;

    for (const c of components) {
      if (!heroEnded) {
        if (c.component_type === 'search_bar' || c.component_type === 'notice_bar') {
          // 标记为 hero 模式，确保 props 不为 null
          const safeProps = (c.props && typeof c.props === 'object') ? c.props : {};
          heroComponents.push(Object.assign({}, c, {
            props: Object.assign({}, safeProps, { _hero: true }),
          }));
        } else if (c.component_type === 'promo_banner' && c.props && c.props.style === 'glass') {
          heroComponents.push(c);
        } else {
          // 遇到非 hero 组件，后续全部归入 body
          heroEnded = true;
          bodyComponents.push(c);
        }
      } else {
        bodyComponents.push(c);
      }
    }

    return { heroComponents, bodyComponents };
  },

  onSearchTap() {
    wx.navigateTo({ url: '/pages/search/search' });
  },
});
