// components/goods-card/index.js
const imageUtil = require('../../utils/image');

Component({
  options: { multipleSlots: true, addGlobalClass: true },
  properties: {
    item: { type: Object, value: {}, observer: '_normalizeItem' },
    layout: { type: String, value: 'grid' }, // grid | list
    showCartBtn: { type: Boolean, value: false },
  },
  data: {
    _cover: '',
    _name: '',
    _price: '0.00',
    _marketPrice: '',
    _sales: 0,
    _subtitle: '',
    _badge: '',
    _badgeType: '',
    _showTags: false,
    _freeShip: false,
    _auth: false,
  },
  methods: {
    _normalizeItem(item) {
      if (!item || typeof item !== 'object') return;

      // 判断标签
      const isHot = item.is_hot || Number(item.total_sales || item.sales || 0) > 100;
      const isNew = item.is_new || item.is_latest;
      const isSale = item.market_price && Number(item.market_price) > Number(item.sale_price || item.price || 0);
      let badge = '';
      let badgeType = '';
      if (isHot) { badge = '热卖'; badgeType = 'badge-hot'; }
      else if (isNew) { badge = '新品'; badgeType = 'badge-new'; }
      else if (isSale) { badge = '特惠'; badgeType = 'badge-sale'; }

      // 市场价
      const salePrice = item.sale_price || item.price || item.goods_price || '0.00';
      const marketPrice = item.market_price && Number(item.market_price) > Number(salePrice)
        ? item.market_price : '';

      this.setData({
        _cover: imageUtil.getItemCover(item),
        _name: item.goods_name || item.name || item.title || '商品名称',
        _price: salePrice,
        _marketPrice: marketPrice,
        _sales: item.total_sales !== undefined ? item.total_sales : (item.sales || item.sold_count || 0),
        _subtitle: item.subtitle || item.sub_title || item.description || '',
        _badge: badge,
        _badgeType: badgeType,
        _showTags: true,
        _freeShip: item.is_free_shipping !== false,
        _auth: true,
      });
    },
    onTap() {
      const id = this.data.item.id || this.data.item.goods_id;
      if (!id) return;
      wx.navigateTo({ url: `/pages/item-detail/item-detail?id=${id}` });
    },
    onCoverError() {
      // 图片加载失败时清除 cover，触发 fallback 占位图
      this.setData({ _cover: '' });
    },
    onCartTap(e) {
      // 阻止冒泡，避免触发卡片点击
      if (e && e.stopPropagation) e.stopPropagation();
      const id = this.data.item.id || this.data.item.goods_id;
      if (!id) return;
      this.triggerEvent('cart', { id, item: this.data.item });
    },
  },
});
