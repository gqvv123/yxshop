// pages/item-detail/item-detail.js
const api = require('../../api/index');
const auth = require('../../utils/auth');
const config = require('../../config/index');
const imageUtil = require('../../utils/image');

const AVATAR_COLORS = [
  'linear-gradient(135deg, #2563eb, #3b82f6)',
  'linear-gradient(135deg, #8b5cf6, #a78bfa)',
  'linear-gradient(135deg, #10b981, #34d399)',
  'linear-gradient(135deg, #f59e0b, #fbbf24)',
  'linear-gradient(135deg, #ef4444, #f87171)',
  'linear-gradient(135deg, #06b6d4, #22d3ee)',
];

Page({
  data: {
    id: '',
    item: null,
    images: [],
    displayPrice: '0.00',
    marketPrice: '',
    discountText: '',
    favorited: false,
    showSku: false,
    skuAction: 'cart',
    loading: true,
    selectedSpecText: '',
    specList: [],
    // 评价
    reviews: [],
    reviewSummary: {},
    reviewRate: 100,
    reviewTags: [],
    // 商品咨询/问答
    consultations: [],
    consultationTotal: 0,
    showConsultInput: false,
    consultContent: '',
    submittingConsult: false,
    // 店铺信息
    shopInfo: null,
    // 促销赠品
    promoText: '',
    // 相关推荐
    relatedItems: [],
  },

  onLoad(options) {
    const id = options.id || '';
    if (!id) {
      wx.showToast({ title: '商品不存在', icon: 'none' });
      return;
    }
    this.setData({ id });

    // 接收分享链接中的推荐人ID
    if (options.ref) {
      const ref = parseInt(options.ref, 10) || 0;
      if (ref > 0) {
        const app = getApp();
        if (app && app.globalData) {
          app.globalData.referrerId = ref;
        }
        try { wx.setStorageSync('referrerId', ref); } catch (e) {}
      }
    }

    this.loadDetail();
    if (auth.isLoggedIn()) this.checkFavorite();
  },

  // ===== 加载商品详情 =====
  async loadDetail() {
    this.setData({ loading: true });
    try {
      const item = await api.item.getDetail(this.data.id);
      if (!item) {
        this.setData({ loading: false });
        return;
      }
      this.processItem(item);
    } catch (e) {
      // toast 已弹
    } finally {
      this.setData({ loading: false });
    }
  },

  processItem(item) {
    // 将 description（HTML富文本）映射到 detail_html，供 rich-text 组件渲染
    if (item.description && !item.detail_html) {
      item.detail_html = item.description;
    }

    // 解析图片列表
    const images = this.resolveImages(item);

    // 价格处理
    const salePrice = item.sale_price || item.price || item.goods_price || '0.00';
    const marketPrice = item.price || item.market_price || '';
    const mp = parseFloat(marketPrice);
    const sp = parseFloat(salePrice);
    let discountText = '';
    if (mp > 0 && sp > 0 && mp > sp) {
      const diff = (mp - sp).toFixed(0);
      discountText = '限时直降' + diff;
    }

    // 规格参数
    const specList = this.buildSpecList(item);

    // 店铺信息
    const shopInfo = this.buildShopInfo(item);

    // 促销赠品
    const promoText = item.gift_info || item.promo_text || '';

    this.setData({
      item,
      images,
      displayPrice: salePrice,
      marketPrice: mp > sp ? marketPrice : '',
      discountText,
      specList,
      shopInfo,
      promoText,
    });

    // 加载评价
    this.loadReviews();
    // 加载咨询
    this.loadConsultations();
    // 加载相关推荐
    this.loadRelatedItems();
    // 记录浏览
    this.recordView();
  },

  // ===== 相关推荐 =====
  async loadRelatedItems() {
    try {
      const res = await api.item.getRelated({ id: this.data.id, page: 1, page_size: 6 }).catch(() => null);
      const data = this.normalizeResponse(res);
      var self = this;
      var list = (data.list || data.data || data || []).filter(function (it) { return it; });
      // 排除当前商品，并解析封面图
      var filtered = list.filter(function (it) {
        return String(it.id || it.goods_id || '') !== String(self.data.id);
      }).map(function (it) {
        it._cover = imageUtil.getItemCover(it);
        return it;
      });
      this.setData({ relatedItems: filtered.slice(0, 6) });
    } catch (e) {
      // ignore
    }
  },

  onRelatedItemTap(e) {
    const id = e.currentTarget.dataset.id;
    if (!id) return;
    wx.navigateTo({
      url: '/pages/item-detail/item-detail?id=' + id,
    });
  },

  resolveImages(item) {
    // 从 item.images 数组解析
    if (Array.isArray(item.images) && item.images.length) {
      return item.images.map(function (img) {
        var url = img.url || img.image_id || '';
        if (typeof url === 'string' && url.trim()) {
          return imageUtil.resolve(url);
        }
        return '';
      }).filter(function (url) { return url; });
    }
    // 降级到单图
    const cover = imageUtil.getItemCover(item);
    return cover ? [cover] : [];
  },

  buildShopInfo(item) {
    var shop = item.shop || item.store || item.merchant || {};
    if (!shop.name && !shop.store_name && !shop.shop_name) return null;
    return {
      name: shop.name || shop.store_name || shop.shop_name || '店铺',
      logo: shop.logo || shop.avatar || '',
      desc: shop.description || shop.desc || '',
      verified: shop.verified || shop.is_verified || false,
      stats: {
        items: shop.item_count || shop.items_count || 0,
        followers: shop.followers || shop.follow_count || 0,
        score: shop.score || shop.experience_score || '9.8',
        goodRate: shop.good_rate || shop.positive_rate || '99%',
      },
    };
  },

  /**
   * 规格参数表
   *
   * 数据源优先级：attributes（属性定义 + 值，权威）> specs（早期平铺格式兜底）。
   * 两者在后端可能来自同一份 item_attrs，同时渲染会重复，所以只在没有 attributes 时才用 specs。
   * 空值一律跳过——历史上 specs 会出现 {name:'某个值', values:['']} 导致 "精选纸品:" 这种空行。
   */
  buildSpecList(item) {
    const list = [];
    const seen = {};

    const push = function (label, value) {
      const l = (label || '').toString().trim();
      let v = (value === undefined || value === null) ? '' : value.toString().trim();
      if (!l || !v) return;
      const key = l + '\u0000' + v;
      if (seen[key]) return;
      seen[key] = 1;
      list.push({ label: l, value: v });
    };

    const attrs = Array.isArray(item.attributes) ? item.attributes : [];
    if (attrs.length) {
      attrs.forEach(function (attr) {
        push(attr.name, attr.value);
      });
    } else if (Array.isArray(item.specs)) {
      item.specs.forEach(function (spec) {
        if (!spec || !spec.name) return;
        const vals = (spec.values || []).filter(function (v) {
          return v !== undefined && v !== null && String(v).trim() !== '';
        });
        push(spec.name, vals.join(' / '));
      });
    }

    // 基础信息补充
    if (item.brand && item.brand.name) push('品牌', item.brand.name);
    if (item.unit) push('单位', item.unit);
    if (item.weight) push('重量', item.weight + 'kg');
    return list;
  },

  // ===== 评价 =====
  async loadReviews() {
    try {
      const res = await api.item.getReviews({ id: this.data.id, page: 1, page_size: 3 }).catch(() => null);
      const data = this.normalizeResponse(res);
      const reviews = (data.list || []).map((rv, idx) => this.normalizeReview(rv, idx));

      // 评价摘要
      let reviewSummary = this.data.item.review_summary || {};
      if (data.summary) reviewSummary = Object.assign(reviewSummary, data.summary);

      const total = reviewSummary.total || data.total || reviews.length;
      const goodRate = reviewSummary.good_rate || reviewSummary.positive_rate || 100;
      const avgRate = reviewSummary.avg_rate || reviewSummary.average_rate || '5.0';

      // 评价标签
      const reviewTags = [];
      if (reviewSummary.tags && Array.isArray(reviewSummary.tags)) {
        reviewSummary.tags.forEach(function (t) {
          reviewTags.push({ label: t.label || t.name, num: t.count || t.num || 0 });
        });
      }

      this.setData({
        reviews,
        reviewSummary: { total: total, avg_rate: avgRate, good_rate: goodRate },
        reviewRate: goodRate,
        reviewTags,
      });
    } catch (e) {
      // ignore
    }
  },

  normalizeResponse(res) {
    if (!res) return {};
    if (res.data) return res.data;
    if (res.list) return res;
    return res;
  },

  normalizeReview(rv, idx) {
    const nickname = rv.user_nickname || rv.nickname || '匿名用户';
    const initial = nickname ? nickname.charAt(0) : '用';
    return {
      id: rv.id || idx,
      nickname: nickname,
      initial: initial,
      avatarBg: AVATAR_COLORS[idx % AVATAR_COLORS.length],
      content: rv.content || '',
      date: rv.created_at || rv.date || '',
      spec: rv.spec_key_name || '',
    };
  },

  onMoreReviews() {
    wx.showModal({
      title: '商品评价',
      content: '该商品共有 ' + ((this.data.reviewSummary && this.data.reviewSummary.total) || this.data.reviews.length || 0) + ' 条评价。\n\n更多评价功能即将上线，敬请期待。',
      showCancel: false,
      confirmText: '知道了',
      confirmColor: '#2563eb',
    });
  },

  // ===== 商品咨询/问答 =====
  async loadConsultations() {
    try {
      const res = await api.item.getConsultations({ id: this.data.id, page: 1, page_size: 3 }).catch(() => null);
      const data = this.normalizeResponse(res);
      const list = (data.list || data || []).map(function (c, idx) {
        return {
          id: c.id || idx,
          question: c.question || c.content || '',
          answer: c.answer || c.reply || '',
          nickname: c.user_nickname || c.nickname || '匿名用户',
          initial: (c.user_nickname || c.nickname || '用').charAt(0),
          avatarBg: AVATAR_COLORS[idx % AVATAR_COLORS.length],
          created_at: c.created_at || c.date || '',
          reply_at: c.reply_at || '',
        };
      });
      this.setData({
        consultations: list,
        consultationTotal: data.total || list.length,
      });
    } catch (e) {}
  },

  onShowConsultInput() {
    if (!this.requireLogin()) return;
    this.setData({ showConsultInput: true, consultContent: '' });
  },

  onConsultInput(e) {
    this.setData({ consultContent: e.detail.value });
  },

  onConsultBlur(e) {
    this.setData({ consultContent: e.detail.value });
  },

  onCancelConsult() {
    this.setData({ showConsultInput: false, consultContent: '' });
  },

  async onSubmitConsult() {
    if (this.data.submittingConsult) return;
    var content = (this.data.consultContent || '').trim();
    if (!content) {
      wx.showToast({ title: '请输入咨询内容', icon: 'none' });
      return;
    }
    this.setData({ submittingConsult: true });
    try {
      await api.item.consult({ item_id: this.data.id, content: content });
      wx.showToast({ title: '提交成功', icon: 'success' });
      this.setData({ showConsultInput: false, consultContent: '', submittingConsult: false });
      this.loadConsultations();
    } catch (e) {
      this.setData({ submittingConsult: false });
      wx.showToast({ title: (e && e.message) || '提交失败', icon: 'none' });
    }
  },

  onMoreConsultations() {
    wx.showModal({
      title: '商品咨询',
      content: '该商品共有 ' + (this.data.consultationTotal || 0) + ' 条咨询。\n\n更多咨询功能即将上线，敬请期待。',
      showCancel: false,
      confirmText: '知道了',
      confirmColor: '#2563eb',
    });
  },

  // ===== 浏览记录 =====
  recordView() {
    if (!auth.isLoggedIn()) return;
    api.item.view({ item_id: this.data.id }).catch(() => {});
  },

  // ===== 收藏 =====
  async checkFavorite() {
    try {
      const r = await api.favorite.check({ item_id: this.data.id });
      this.setData({ favorited: !!(r && (r.is_favorited || r.favorited)) });
    } catch (e) { /* ignore */ }
  },

  async onToggleFavorite() {
    if (!this.requireLogin()) return;
    try {
      if (this.data.favorited) {
        await api.favorite.remove({ item_id: this.data.id });
        this.setData({ favorited: false });
        wx.showToast({ title: '已取消收藏', icon: 'none' });
      } else {
        await api.favorite.add({ item_id: this.data.id });
        this.setData({ favorited: true });
        wx.showToast({ title: '收藏成功', icon: 'success' });
      }
    } catch (e) { /* toast 已弹 */ }
  },

  // ===== SKU 弹窗 =====
  showAddCart() {
    this.setData({ showSku: true, skuAction: 'cart' });
  },
  showBuyNow() {
    this.setData({ showSku: true, skuAction: 'buy' });
  },
  hideSku() {
    this.setData({ showSku: false });
  },

  async onConfirmSku(e) {
    if (!this.requireLogin()) return;
    const { sku_id, spec_key, spec_key_name, price, stock, quantity, action } = e.detail;

    // 更新已选规格文本
    this.setData({ selectedSpecText: spec_key_name || '已选择' });

    if (action === 'cart') {
      try {
        await api.cart.add({
          item_id: this.data.id,
          sku_id,
          spec_key,
          spec_key_name,
          quantity,
          app_id: (this.data.item && this.data.item.app_id) || config.appId,
        });
        wx.showToast({ title: '已加入购物车', icon: 'success' });
        this.hideSku();
      } catch (e) { /* toast 已弹 */ }
      return;
    }

    // 立即购买
    wx.setStorageSync('yxshop:checkoutItems', [{
      item_id: this.data.id,
      sku_id,
      spec_key,
      spec_key_name,
      quantity,
    }]);
    this.hideSku();
    wx.navigateTo({ url: '/pages/order/confirm/confirm?from=detail' });
  },

  // ===== 主图轮播 =====
  onSwiperChange() {},

  onHeroImgError(e) {
    const idx = e.currentTarget.dataset.index;
    if (idx === undefined) return;
    const images = this.data.images.slice();
    images[idx] = '';
    this.setData({ images: images.filter(function (u) { return u; }) });
  },

  // ===== 导航 =====
  onBack() {
    const pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/home/home' });
    }
  },

  onShare() {
    wx.showShareMenu({ withShareTicket: true });
  },

  onShareAppMessage() {
    const auth = require('../../utils/auth');
    const user = auth.getUser();
    const refParam = user && user.id ? ('&ref=' + user.id) : '';
    return {
      title: this.data.item ? (this.data.item.name || this.data.item.goods_name) : '商品详情',
      path: '/pages/item-detail/item-detail?id=' + this.data.id + refParam,
    };
  },

  goCart() {
    wx.switchTab({ url: '/pages/cart/cart' });
  },

  onShopEnter() {
    var shop = this.data.shopInfo;
    if (!shop) return;
    wx.showToast({ title: '店铺页面即将上线', icon: 'none' });
  },

  requireLogin() {
    if (auth.isLoggedIn()) return true;
    wx.navigateTo({ url: '/pages/login/login' });
    return false;
  },

  noop() {},
});
