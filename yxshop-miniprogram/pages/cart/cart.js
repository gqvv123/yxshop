// pages/cart/cart.js
const api = require('../../api/index');
const auth = require('../../utils/auth');
const config = require('../../config/index');
const imageUtil = require('../../utils/image');
const { fmtPrice } = require('../../utils/format');

Page({
  data: {
    // 店铺分组（来自后端 stores 数组）
    stores: [],
    // 所有商品扁平列表（用于结算）
    allItems: [],
    // 选中状态 { cart_id: true }
    selected: {},
    // 店铺选中状态 { store_id: true }
    storeSelected: {},
    allSelected: false,
    totalPrice: '0.00',
    totalQty: 0,
    totalAmount: 0,           // 原始数值
    savedAmount: 0,           // 已优惠金额
    savedAmountText: '0.00',
    isLoggedIn: false,
    loading: true,
    isEditing: false,         // 管理模式
    // 推荐商品
    recommendItems: [],
    // 优惠券提示文字
    couponHint: '',
    statusBarHeight: 20,
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
  },

  onShow() {
    this.setData({ isLoggedIn: auth.isLoggedIn() });
    if (this.data.isLoggedIn) {
      this.loadCart();
      this.loadRecommend();
    } else {
      this.setData({ stores: [], allItems: [], loading: false });
    }
    // 同步自定义 tabbar 选中状态
    const tabBar = this.getTabBar && this.getTabBar();
    if (tabBar) {
      tabBar.setSelected();
      if (typeof tabBar.refreshCartCount === 'function') {
        tabBar.refreshCartCount();
      }
    }
  },

  // ===== 加载购物车 =====
  async loadCart() {
    this.setData({ loading: true });
    try {
      const res = await api.cart.getList({ app_id: config.appId });
      const stores = (res && res.stores) || [];
      const allItems = [];

      // 处理每个店铺和商品
      stores.forEach((store) => {
        // 店铺提示信息（满减券/包邮）
        store.store_hint = '';
        store.store_hint_type = '';

        if (store.items && store.items.length) {
          store.items.forEach((it) => {
            // 统一 ID 字段
            it._id = it.id || it.cart_id;
            // 解析图片 URL
            it._cover = this._resolveImage(it);
            // 规格标签数组
            it._skuTags = this._parseSkuTags(it);
            // 价格格式化
            it._priceText = fmtPrice(it.price);
            // 市场价（划线价）
            it._marketPriceText = this._getMarketPrice(it);
            // 商品角标
            it._badge = '';
            it._badgeType = '';
            // 左滑删除偏移量
            it._offset = 0;
            it._anim = false;
            allItems.push(it);
          });
        }
      });

      // 根据后端 is_selected 初始化选中状态
      const selected = {};
      allItems.forEach((it) => {
        if (it._id !== undefined && it._id !== null) {
          selected[it._id] = it.is_selected === undefined ? true : Number(it.is_selected) === 1;
        }
      });

      this.setData({ stores, allItems, selected });
      this._refreshStoreSelected();
      this.recalcTotal();

      // 异步加载优惠券提示和店铺提示
      this._loadCouponHint();
    } catch (e) {
      this.setData({ stores: [], allItems: [], selected: {}, storeSelected: {} });
    } finally {
      this.setData({ loading: false });
    }
  },

  // ===== 推荐商品 =====
  async loadRecommend() {
    try {
      const res = await api.item.getRecommended({ page_size: 4 });
      const list = (res && res.list) || (Array.isArray(res) ? res : []) || [];
      this.setData({ recommendItems: list });
    } catch (e) {
      // 静默失败
    }
  },

  // ===== 优惠券提示 =====
  async _loadCouponHint() {
    try {
      const res = await api.cart.checkoutPreview({ app_id: config.appId });
      // 可用优惠券提示
      const coupons = (res && res.available_coupons) || [];
      if (coupons.length > 0) {
        const best = coupons[0];
        const hint = `已为你匹配 ${coupons.length} 张优惠券，满 ${best.min_amount} 减 ${best.discount_amount}`;
        this.setData({ couponHint: hint });

        // 给每个店铺添加提示
        this._updateStoreHints(coupons);
      } else {
        this.setData({ couponHint: '' });
      }

      // 已优惠金额
      const saved = (res && res.best_coupon && res.best_coupon.discount_amount) || 0;
      this.setData({
        savedAmount: saved,
        savedAmountText: fmtPrice(saved),
      });
    } catch (e) {
      // 静默失败
    }
  },

  // ===== 更新店铺提示（满减券/包邮） =====
  _updateStoreHints(coupons) {
    const stores = this.data.stores.map((store) => {
      const s = { ...store };
      // 默认：自营商品显示包邮，有满减券则显示满减
      const hasFreeShipping = store.items && store.items.some((it) => {
        if (it.item && it.item.is_free_shipping !== undefined) {
          return Number(it.item.is_free_shipping) === 1;
        }
        return false;
      });
      if (coupons.length > 0) {
        const c = coupons[0];
        s.store_hint = `满${c.min_amount}减${c.discount_amount}`;
        s.store_hint_type = 'coupon';
      } else if (hasFreeShipping) {
        s.store_hint = '包邮';
        s.store_hint_type = 'shipping';
      }
      return s;
    });
    this.setData({ stores });
  },

  // ===== 图片处理 =====
  _resolveImage(cartItem) {
    // 优先使用快照字段 item_image
    if (cartItem.item_image) {
      return imageUtil.resolve(cartItem.item_image);
    }
    // 降级到关联商品的主图
    if (cartItem.item) {
      const cover = imageUtil.getItemCover(cartItem.item);
      if (cover) return cover;
    }
    return '';
  },

  // ===== 市场价 =====
  _getMarketPrice(cartItem) {
    // 从关联商品获取市场价
    if (cartItem.item && cartItem.item.market_price) {
      const mp = Number(cartItem.item.market_price);
      const sp = Number(cartItem.price || 0);
      if (mp > sp) return fmtPrice(mp);
    }
    return '';
  },

  // ===== SKU 标签处理 =====
  _parseSkuTags(cartItem) {
    const specKeyName = cartItem.spec_key_name || '';
    if (!specKeyName) return [];
    // 按空格或逗号分割规格
    return specKeyName.split(/[\s,，;；]+/).filter(Boolean);
  },

  // ===== 图片加载失败 =====
  onImgError(e) {
    const { idx, sidx } = e.currentTarget.dataset;
    if (sidx !== undefined && idx !== undefined) {
      this.setData({ [`stores[${sidx}].items[${idx}]._cover`]: '' });
    }
  },

  // ===== 计算店铺选中状态 =====
  _refreshStoreSelected() {
    const storeSelected = {};
    this.data.stores.forEach((store) => {
      const items = store.items || [];
      if (items.length === 0) {
        storeSelected[store.store_id] = false;
        return;
      }
      const allChecked = items.every((it) => this.data.selected[it._id]);
      storeSelected[store.store_id] = allChecked;
    });
    this.setData({ storeSelected });
  },

  // ===== 重新计算合计 =====
  recalcTotal() {
    let total = 0;
    let qty = 0;
    let allSelected = this.data.allItems.length > 0;
    this.data.allItems.forEach((it) => {
      if (this.data.selected[it._id]) {
        total += Number(it.price || 0) * Number(it.quantity || 0);
        qty += Number(it.quantity || 0);
      } else {
        allSelected = false;
      }
    });
    this.setData({
      totalPrice: fmtPrice(total),
      totalAmount: total,
      totalQty: qty,
      allSelected,
    });
  },

  // ===== 单个商品选中 =====
  onToggleSelect(e) {
    const id = e.currentTarget.dataset.id;
    const selected = { ...this.data.selected, [id]: !this.data.selected[id] };
    this.setData({ selected });
    this._refreshStoreSelected();
    this.recalcTotal();
  },

  // ===== 店铺全选 =====
  onToggleStore(e) {
    const storeId = Number(e.currentTarget.dataset.storeId);
    const store = this.data.stores.find((s) => Number(s.store_id) === storeId);
    if (!store || !store.items.length) return;
    const nextChecked = !this.data.storeSelected[storeId];
    const selected = { ...this.data.selected };
    store.items.forEach((it) => { selected[it._id] = nextChecked; });
    this.setData({ selected });
    this._refreshStoreSelected();
    this.recalcTotal();
  },

  // ===== 全选 =====
  onToggleAll() {
    const next = !this.data.allSelected;
    const selected = {};
    if (next) {
      this.data.allItems.forEach((it) => { selected[it._id] = true; });
    }
    this.setData({ selected, allSelected: next });
    this._refreshStoreSelected();
    this.recalcTotal();
  },

  // ===== 数量增减 =====
  async onChangeQty(e) {
    const { id, delta } = e.currentTarget.dataset;
    const idx = this.data.allItems.findIndex((x) => x._id == id);
    if (idx === -1) return;
    const item = this.data.allItems[idx];
    const nextQty = Math.max(1, Number(item.quantity) + Number(delta));
    if (nextQty === Number(item.quantity)) return;
    try {
      await api.cart.update({ cart_id: id, quantity: nextQty });
      // 更新 stores 和 allItems 中的数量
      const stores = this.data.stores.map((store) => ({
        ...store,
        items: store.items.map((it) =>
          it._id == id ? { ...it, quantity: nextQty } : it
        ),
      }));
      const allItems = this.data.allItems.map((it) =>
        it._id == id ? { ...it, quantity: nextQty } : it
      );
      this.setData({ stores, allItems });
      this.recalcTotal();
    } catch (e) { /* toast 已弹 */ }
  },

  // ===== 左滑删除：触摸开始 =====
  onSwipeStart(e) {
    this._touchStartX = e.touches[0].clientX;
    this._touchStartY = e.touches[0].clientY;
    // 记录当前滑动的 id
    this._swipeId = e.currentTarget.dataset.id;
  },

  // ===== 左滑删除：触摸结束 =====
  onSwipeEnd(e) {
    if (this._touchStartX === undefined) return;
    const dx = e.changedTouches[0].clientX - this._touchStartX;
    const dy = e.changedTouches[0].clientY - this._touchStartY;
    // 水平滑动距离大于垂直才触发
    if (Math.abs(dx) < Math.abs(dy)) return;
    const id = e.currentTarget.dataset.id;
    const ACTION_WIDTH = 80; // 删除按钮宽度 px（160rpx ≈ 80px）

    // 找到当前 item 在 stores 中的路径
    const { sidx, iidx } = this._findItemIndex(id);
    if (sidx === -1) return;

    const isOpen = this.data.stores[sidx].items[iidx]._offset === -ACTION_WIDTH;
    let targetOffset = 0;
    let targetAnim = true;

    if (dx < -30 && !isOpen) {
      // 左滑打开
      targetOffset = -ACTION_WIDTH;
    } else if (dx > 30 && isOpen) {
      // 右滑关闭
      targetOffset = 0;
    } else {
      // 距离不够，保持原状
      targetOffset = isOpen ? -ACTION_WIDTH : 0;
    }

    // 先关闭其他已打开的项
    const stores = this.data.stores.map((store) => ({
      ...store,
      items: store.items.map((it) => {
        if (it._id == id) {
          return { ...it, _offset: targetOffset, _anim: true };
        }
        // 关闭其他打开的
        if (it._offset && it._offset !== 0) {
          return { ...it, _offset: 0, _anim: true };
        }
        return it;
      }),
    }));
    this.setData({ stores });
  },

  // ===== 辅助：根据 id 找到 stores 中的索引 =====
  _findItemIndex(id) {
    for (let s = 0; s < this.data.stores.length; s++) {
      for (let i = 0; i < this.data.stores[s].items.length; i++) {
        if (this.data.stores[s].items[i]._id == id) {
          return { sidx: s, iidx: i };
        }
      }
    }
    return { sidx: -1, iidx: -1 };
  },

  // ===== 关闭所有已打开的滑动项 =====
  _closeAllSwipe() {
    let changed = false;
    const stores = this.data.stores.map((store) => ({
      ...store,
      items: store.items.map((it) => {
        if (it._offset && it._offset !== 0) {
          changed = true;
          return { ...it, _offset: 0, _anim: true };
        }
        return it;
      }),
    }));
    if (changed) this.setData({ stores });
  },

  // ===== 删除 =====
  async onRemove(e) {
    const id = e.currentTarget.dataset.id;
    const ok = await new Promise((r) => {
      wx.showModal({ title: '提示', content: '从购物车中移除该商品？', success: (res) => r(res.confirm) });
    });
    if (!ok) {
      // 取消删除则收回滑动
      const { sidx, iidx } = this._findItemIndex(id);
      if (sidx !== -1) {
        this.setData({ [`stores[${sidx}].items[${iidx}]._offset`]: 0, [`stores[${sidx}].items[${iidx}]._anim`]: true });
      }
      return;
    }
    try {
      await api.cart.remove({ cart_id: id });
      // 从 stores 和 allItems 中移除
      const stores = this.data.stores
        .map((store) => ({
          ...store,
          items: store.items.filter((it) => it._id != id),
        }))
        .filter((store) => store.items.length > 0); // 移除空店铺
      const allItems = this.data.allItems.filter((x) => x._id != id);
      const selected = { ...this.data.selected };
      delete selected[id];
      this.setData({ stores, allItems, selected });
      this._refreshStoreSelected();
      this.recalcTotal();
      // 同步购物车角标
      const tabBar = this.getTabBar && this.getTabBar();
      if (tabBar && typeof tabBar.refreshCartCount === 'function') {
        tabBar.refreshCartCount();
      }
    } catch (e) { /* toast 已弹 */ }
  },

  // ===== 管理模式切换 =====
  onToggleEdit() {
    this.setData({ isEditing: !this.data.isEditing });
  },

  // ===== 返回 =====
  onBack() {
    const pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/home/home' });
    }
  },

  // ===== 分享 =====
  onShare() {
    wx.showShareMenu({ withShareTicket: true });
    wx.showToast({ title: '点击右上角分享', icon: 'none' });
  },

  // ===== 收藏（将选中商品加入收藏） =====
  async onFavorite() {
    const checkedItems = this.data.allItems.filter((it) => this.data.selected[it._id]);
    if (!checkedItems.length) {
      return wx.showToast({ title: '请先勾选商品', icon: 'none' });
    }
    try {
      for (const it of checkedItems) {
        const itemId = it.item_id || it.goods_id;
        if (itemId) {
          await api.favorite.add({ item_id: itemId });
        }
      }
      wx.showToast({ title: '收藏成功', icon: 'success' });
    } catch (e) { /* toast 已弹 */ }
  },

  // ===== 去领券 =====
  onGoCoupon() {
    wx.navigateTo({ url: '/pages/coupon/coupon' });
  },

  // ===== 结算 =====
  onCheckout() {
    const checkedItems = this.data.allItems.filter(
      (it) => this.data.selected[it._id]
    );
    if (!checkedItems.length) {
      return wx.showToast({ title: '请先勾选商品', icon: 'none' });
    }
    const payload = checkedItems.map((it) => ({
      item_id: it.item_id || it.goods_id,
      quantity: it.quantity,
      spec_key: it.spec_key,
      spec_key_name: it.spec_key_name,
      cart_id: it._id,
    }));
    wx.setStorageSync('yxshop:checkoutItems', payload);
    wx.navigateTo({ url: '/pages/order/confirm/confirm?from=cart' });
  },

  // ===== 推荐商品加入购物车 =====
  async onRecAddCart(e) {
    const { id } = e.detail;
    if (!id) return;
    if (!auth.isLoggedIn()) {
      wx.showModal({
        title: '提示',
        content: '请先登录后再加入购物车',
        confirmText: '去登录',
        success: (modalRes) => {
          if (modalRes.confirm) wx.navigateTo({ url: '/pages/login/login' });
        },
      });
      return;
    }
    try {
      await api.cart.add({ item_id: id, quantity: 1, app_id: config.appId });
      wx.showToast({ title: '已加入购物车', icon: 'success' });
      this.loadCart();
      const tabBar = this.getTabBar && this.getTabBar();
      if (tabBar && typeof tabBar.refreshCartCount === 'function') {
        tabBar.refreshCartCount();
      }
    } catch (e) { /* toast 已弹 */ }
  },

  // ===== 去逛逛 / 登录 =====
  onLogin() {
    if (this.data.isLoggedIn) {
      wx.switchTab({ url: '/pages/home/home' });
    } else {
      wx.navigateTo({ url: '/pages/login/login' });
    }
  },

  // ===== 分享给好友 =====
  onShareAppMessage() {
    return {
      title: '快来看看这些好物',
      path: '/pages/cart/cart',
    };
  },
});
