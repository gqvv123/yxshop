const api = require('../../../api/index');
const imageUtil = require('../../../utils/image');

Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    title: '',
    icon: '',
    tagText: '',
    showMore: false,
    moreLink: '',
    columns: 2,
    showPrice: true,
    showSales: false,
    showCart: true,
    items: [],
    loading: true,
    loadError: false,
  },
  lifetimes: {
    attached() {
      this.loadData();
    },
  },
  observers: {
    props(val) {
      if (!val) return;
      this.setData({
        title: val.title || '',
        icon: val.icon || '',
        tagText: val.tag_text || '',
        showMore: val.show_more !== false,
        moreLink: val.more_link || '',
        columns: val.columns || 2,
        showPrice: val.show_price !== false,
        showSales: val.show_sales === true,
        showCart: val.show_cart !== false,
      });
    },
  },
  methods: {
    async loadData() {
      this.setData({ loading: true, loadError: false });
      try {
        const p = this.properties.props || {};
        const sourceType = p.source_type || 'hot';
        const limit = p.limit || p.display_count || 6;
        const appId = (getApp() && getApp().globalData && getApp().globalData.appId) || 10001;
        let rawItems = [];

        if (sourceType === 'manual' && p.item_ids && p.item_ids.length) {
          const res = await api.item.getList({ ids: p.item_ids.join(','), limit, app_id: appId }).catch(() => null);
          rawItems = this.normalizeList(res);
        } else if (sourceType === 'category' && p.category_id) {
          const res = await api.item.getList({ category_id: p.category_id, limit, sort_by: p.sort_by || 'newest', app_id: appId }).catch(() => null);
          rawItems = this.normalizeList(res);
        } else if (sourceType === 'tag' && p.tag_id) {
          const res = await api.item.getList({ tag_id: p.tag_id, limit, sort_by: p.sort_by || 'newest', app_id: appId }).catch(() => null);
          rawItems = this.normalizeList(res);
        } else {
          const res = await api.item.getHot({ limit, app_id: appId }).catch(() => null);
          rawItems = this.normalizeList(res);
        }

        const items = (rawItems || []).map((item) => this.normalizeItem(item));
        this.setData({ items });
      } catch (e) {
        this.setData({ loadError: true });
      } finally {
        this.setData({ loading: false });
      }
    },

    normalizeList(input) {
      if (Array.isArray(input)) return input;
      if (input && Array.isArray(input.data)) return input.data;
      if (input && Array.isArray(input.list)) return input.list;
      if (input && Array.isArray(input.items)) return input.items;
      return [];
    },

    normalizeItem(item) {
      if (!item) return item;
      var tags = [];
      if (item.is_free_shipping || item.free_shipping) {
        tags.push({ text: '包邮', type: 'success' });
      }
      if (item.is_genuine || item.genuine) {
        tags.push({ text: '正品', type: 'primary' });
      }
      if (item.tag_names && Array.isArray(item.tag_names)) {
        item.tag_names.forEach(function (t) {
          tags.push({ text: t, type: 'default' });
        });
      }
      return {
        id: item.id || item.goods_id,
        name: item.goods_name || item.name || item.title || '商品名称',
        subtitle: item.subtitle || item.sub_title || item.description || '',
        price: item.sale_price || item.price || item.goods_price || '0.00',
        original_price: item.original_price || item.market_price || '',
        sales: item.total_sales !== undefined ? item.total_sales : (item.sales || item.sold_count || 0),
        cover: imageUtil.getItemCover(item),
        badge: item.badge || '',
        tags: tags,
      };
    },

    onRetry() {
      this.loadData();
    },
    onCoverError(e) {
      // 图片加载失败时清除 cover，触发 fallback 占位图
      var idx = e.currentTarget.dataset.index;
      var items = this.data.items.slice();
      if (items[idx]) {
        items[idx].cover = '';
        this.setData({ items: items });
      }
    },
    onMoreTap() {
      const link = this.data.moreLink;
      if (!link) return;
      if (/^\//.test(link)) {
        wx.navigateTo({ url: link });
      }
    },
    onItemTap(e) {
      const id = e.currentTarget.dataset.id;
      if (!id) return;
      wx.navigateTo({ url: '/pages/item-detail/item-detail?id=' + id });
    },
    onAddCart(e) {
      const id = e.currentTarget.dataset.id;
      if (!id) return;
      // 触发添加购物车，由父页面处理或直接调用 API
      this.triggerEvent('addcart', { id: id });
    },
  },
});
