const api = require('../../../api/index');

Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    title: '',
    moreText: '',
    moreLink: '',
    columns: 4,
    sourceType: 'system',
    isHot: 0,
    limit: 8,
    items: [],
    _loaded: false,
  },
  observers: {
    props(val) {
      if (!val) return;

      var sourceType = val.source_type || 'system';
      var customItems = (val.items && val.items.length > 0) ? val.items : [];

      this.setData({
        title: val.title || '品牌专区',
        moreText: val.more_text || '查看全部',
        moreLink: val.more_link || '',
        columns: val.columns || 4,
        sourceType: sourceType,
        isHot: val.is_hot || 0,
        limit: val.limit || 8,
        items: sourceType === 'custom' ? customItems : [],
      });

      // system 模式：自动拉取系统品牌
      if (sourceType === 'system' && !this.data._loaded) {
        this.fetchSystemBrands();
      }
    },
  },
  methods: {
    /**
     * 从后端拉取系统品牌列表
     * source_type === 'system' 时调用
     */
    async fetchSystemBrands() {
      if (this.data._loaded) return;
      this.setData({ _loaded: true });

      try {
        var isHot = this.data.isHot;
        var limit = this.data.limit || 8;

        // 不传 app_id，让 API 返回所有启用品牌（避免 app_id 不匹配导致空结果）
        var res = await api.brand.getList({ is_hot: isHot, limit: limit });
        var list = Array.isArray(res) ? res : (res && res.list) || [];

        // 映射为组件统一格式 {name, logo, link}
        var items = list.map(function (b) {
          return {
            name: b.name || '',
            logo: b.logo || '',
            link: b.link || ('/pages/search/search?keyword=' + encodeURIComponent(b.name || '')),
          };
        });

        if (items.length > 0) {
          this.setData({ items: items });
        }
      } catch (e) {
        // 拉取失败静默处理，组件因 items 为空不会渲染
      }
    },

    onItemTap(e) {
      const idx = e.currentTarget.dataset.index;
      const item = this.data.items[idx];
      if (!item || !item.link) return;
      if (/^\//.test(item.link)) {
        wx.navigateTo({ url: item.link });
      } else if (/^https?:/.test(item.link)) {
        wx.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(item.link) });
      }
    },
    onMoreTap() {
      const link = this.data.moreLink;
      if (!link) return;
      if (/^\//.test(link)) {
        wx.navigateTo({ url: link });
      }
    },
  },
});
