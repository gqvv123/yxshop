// custom-tab-bar/index.js
// 使用 mp-icon 矢量图标，对照设计稿实现

// page_path → 图标名映射
var PATH_ICON_MAP = {
  '/pages/home/home': { icon: 'home', iconActive: 'home' },
  '/pages/category/category': { icon: 'grid', iconActive: 'grid' },
  '/pages/cart/cart': { icon: 'shopping-cart', iconActive: 'shopping-cart' },
  '/pages/order/list/list': { icon: 'file-text', iconActive: 'file-text' },
  '/pages/profile/profile': { icon: 'user-circle', iconActive: 'user-circle' },
};

// 默认 5 项 tab — 与 app.json tabBar.list 严格一致
var DEFAULT_LIST = [
  { text: '首页', icon: 'home', iconActive: 'home', pagePath: '/pages/home/home', requireLogin: false, isCart: false, badge: 0, useCustomIcon: false },
  { text: '分类', icon: 'grid', iconActive: 'grid', pagePath: '/pages/category/category', requireLogin: false, isCart: false, badge: 0, useCustomIcon: false },
  { text: '购物车', icon: 'shopping-cart', iconActive: 'shopping-cart', pagePath: '/pages/cart/cart', requireLogin: true, isCart: true, badge: 0, useCustomIcon: false },
  { text: '订单', icon: 'file-text', iconActive: 'file-text', pagePath: '/pages/order/list/list', requireLogin: false, isCart: false, badge: 0, useCustomIcon: false },
  { text: '我的', icon: 'user-circle', iconActive: 'user-circle', pagePath: '/pages/profile/profile', requireLogin: false, isCart: false, badge: 0, useCustomIcon: false },
];

Component({
  data: {
    selected: 0,
    color: '#94a3b8',
    selectedColor: '#2563eb',
    backgroundColor: '#FFFFFF',
    list: DEFAULT_LIST.slice(),
  },

  lifetimes: {
    attached() {
      // 确保 list 始终为数组，防止渲染层崩溃
      if (!Array.isArray(this.data.list)) {
        this.setData({ list: DEFAULT_LIST.slice() });
      }
      // 尝试从本地缓存恢复后台 tabbar 配置
      // 这样非首页（如收藏、购物车）直接打开时也能显示后台配置的颜色/文字
      this.restoreFromCache();
      this.setSelected();
    },
  },

  pageLifetimes: {
    show() {
      this.setSelected();
    },
  },

  methods: {
    /**
     * 接收后端 tabbar 配置并合并到默认列表
     * 策略：始终保持 5 项标准 tab 不变，只从后端数据中合并 text/colors/requireLogin/自定义图标
     * 这样即使后端数据库还是旧的 4 项配置，前端也能正确显示 5 个 tab
     */
    updateConfig: function (tabbar) {
      var color = (tabbar && tabbar.color) || '#94a3b8';
      var selectedColor = (tabbar && tabbar.selected_color) || '#2563eb';
      var backgroundColor = (tabbar && tabbar.background_color) || '#FFFFFF';

      // 以后端 items 建立 page_path → item 的索引
      var backendMap = {};
      if (tabbar && tabbar.items && tabbar.items.length) {
        for (var i = 0; i < tabbar.items.length; i++) {
          var bi = tabbar.items[i];
          if (bi.page_path) {
            backendMap[bi.page_path] = bi;
          }
        }
      }

      // 始终基于默认 5 项构建，合并后端的 text/require_login/自定义图标
      var newList = [];
      for (var d = 0; d < DEFAULT_LIST.length; d++) {
        var def = DEFAULT_LIST[d];
        var bk = backendMap[def.pagePath];
        var iconInfo = PATH_ICON_MAP[def.pagePath] || { icon: 'home', iconActive: 'home' };
        var useCustomIcon = !!(bk && bk.icon_path && bk.selected_icon_path);

        newList.push({
          text: (bk && bk.text) || def.text,
          icon: useCustomIcon ? bk.icon_path : iconInfo.icon,
          iconActive: useCustomIcon ? bk.selected_icon_path : iconInfo.iconActive,
          useCustomIcon: useCustomIcon,
          pagePath: def.pagePath,
          requireLogin: (bk && bk.require_login) || def.requireLogin,
          isCart: def.isCart,
          badge: 0,
        });
      }

      this.setData({
        color: color,
        selectedColor: selectedColor,
        backgroundColor: backgroundColor,
        list: newList,
      });

      // 缓存到本地存储，供非首页 tabbar 组件实例恢复使用
      try {
        wx.setStorageSync('yxshop:tabbar_config', {
          color: color,
          selected_color: selectedColor,
          background_color: backgroundColor,
          items: tabbar && tabbar.items ? tabbar.items : [],
        });
      } catch (e) {
        // ignore
      }

      this.setSelected();
    },

    /**
     * 从本地缓存恢复后台 tabbar 配置
     * 用于非首页直接打开时（如分享链接进入收藏页），tabbar 也能显示后台配色
     */
    restoreFromCache: function () {
      try {
        var cached = wx.getStorageSync('yxshop:tabbar_config');
        if (cached && cached.items && cached.items.length) {
          // 复用 updateConfig 逻辑，但不重复写缓存
          var color = cached.color || '#94a3b8';
          var selectedColor = cached.selected_color || '#2563eb';
          var backgroundColor = cached.background_color || '#FFFFFF';

          var backendMap = {};
          for (var i = 0; i < cached.items.length; i++) {
            var bi = cached.items[i];
            if (bi.page_path) {
              backendMap[bi.page_path] = bi;
            }
          }

          var newList = [];
          for (var d = 0; d < DEFAULT_LIST.length; d++) {
            var def = DEFAULT_LIST[d];
            var bk = backendMap[def.pagePath];
            var iconInfo = PATH_ICON_MAP[def.pagePath] || { icon: 'home', iconActive: 'home' };
            var useCustomIcon = !!(bk && bk.icon_path && bk.selected_icon_path);

            newList.push({
              text: (bk && bk.text) || def.text,
              icon: useCustomIcon ? bk.icon_path : iconInfo.icon,
              iconActive: useCustomIcon ? bk.selected_icon_path : iconInfo.iconActive,
              useCustomIcon: useCustomIcon,
              pagePath: def.pagePath,
              requireLogin: (bk && bk.require_login) || def.requireLogin,
              isCart: def.isCart,
              badge: 0,
            });
          }

          this.setData({
            color: color,
            selectedColor: selectedColor,
            backgroundColor: backgroundColor,
            list: newList,
          });
        }
      } catch (e) {
        // ignore
      }
    },

    setSelected: function () {
      var pages = getCurrentPages();
      if (!pages || !pages.length) return;
      var currentPath = '/' + pages[pages.length - 1].route;
      var list = this.data.list;
      for (var i = 0; i < list.length; i++) {
        if (list[i].pagePath === currentPath) {
          this.setData({ selected: i });
          return;
        }
      }
    },

    onItemTap: function (e) {
      var idx = e.currentTarget.dataset.index;
      var item = this.data.list[idx];
      if (!item || !item.pagePath) return;

      if (item.requireLogin) {
        var token = '';
        try {
          token = wx.getStorageSync('yxshop:token') || '';
        } catch (err) {
          // ignore
        }
        if (!token) {
          wx.navigateTo({ url: '/pages/login/login' });
          return;
        }
      }

      wx.switchTab({ url: item.pagePath });
    },

    // 更新购物车角标数量
    updateCartBadge: function (count) {
      var list = this.data.list;
      for (var i = 0; i < list.length; i++) {
        if (list[i].isCart) {
          this.setData({ ['list[' + i + '].badge']: count || 0 });
          return;
        }
      }
    },

    // 从后端拉取购物车数量并更新角标
    refreshCartCount: function () {
      var self = this;
      try {
        var token = wx.getStorageSync('yxshop:token') || '';
        if (!token) {
          this.updateCartBadge(0);
          return;
        }
      } catch (e) {
        return;
      }

      var config = require('../config/index');
      var { get } = require('../utils/request');
      get('/api/v1/cart/count', { app_id: config.appId }, { silent: true, showError: false })
        .then(function (res) {
          var count = (res && res.count) || 0;
          self.updateCartBadge(count);
        })
        .catch(function () {
          // 静默失败，不影响用户操作
        });
    },
  },
});
