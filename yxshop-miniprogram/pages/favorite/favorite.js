const api = require('../../api/index');
const imageUtil = require('../../utils/image');

Page({
  data: {
    statusBarHeight: 20,
    items: [],
    loading: true,
    editing: false,
    allChecked: false,
    checkedCount: 0,
  },

  onLoad() {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
  },

  onShow() {
    this.load();
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  load() {
    var self = this;
    self.setData({ loading: true });
    api.favorite.getList().then(function (res) {
      var raw = Array.isArray(res) ? res : (res && (res.data || res.list)) || [];
      var items = raw.map(function (f) {
        var item = f.item || f;
        var salePrice = item.sale_price || item.price || item.goods_price || '0.00';
        var marketPrice = '';
        if (item.market_price && Number(item.market_price) > Number(salePrice)) {
          marketPrice = item.market_price;
        }
        return {
          realId: (f.item && f.item.id) || f.item_id || f.id,
          id: item.id,
          name: item.name || item.goods_name || item.title || '商品名称',
          coverUrl: imageUtil.getItemCover(item),
          price: salePrice,
          marketPrice: marketPrice,
          sales: (item.total_sales !== undefined ? item.total_sales : (item.sales || 0)) + (item.initial_sales || 0),
          is_hot: item.is_hot || Number(item.total_sales || 0) > 100,
          checked: false,
        };
      });
      self.setData({ items: items, loading: false });
    }).catch(function () {
      self.setData({ items: [], loading: false });
    });
  },

  onItemTap(e) {
    if (this.data.editing) {
      this.onToggleItem(e);
      return;
    }
    var id = e.currentTarget.dataset.id;
    if (id) {
      wx.navigateTo({ url: '/pages/item-detail/item-detail?id=' + id });
    }
  },

  onToggleEdit() {
    var editing = !this.data.editing;
    var items = this.data.items.map(function (it) {
      return Object.assign({}, it, { checked: false });
    });
    this.setData({
      editing: editing,
      items: items,
      allChecked: false,
      checkedCount: 0,
    });
  },

  onToggleItem(e) {
    var id = e.currentTarget.dataset.id;
    var items = this.data.items.map(function (it) {
      if (it.realId == id) return Object.assign({}, it, { checked: !it.checked });
      return it;
    });
    var checkedCount = items.filter(function (it) { return it.checked; }).length;
    this.setData({
      items: items,
      checkedCount: checkedCount,
      allChecked: checkedCount === items.length && items.length > 0,
    });
  },

  onSelectAll() {
    var allChecked = !this.data.allChecked;
    var items = this.data.items.map(function (it) {
      return Object.assign({}, it, { checked: allChecked });
    });
    this.setData({
      items: items,
      allChecked: allChecked,
      checkedCount: allChecked ? items.length : 0,
    });
  },

  onRemoveSelected() {
    var self = this;
    var toRemove = self.data.items.filter(function (it) { return it.checked; });
    if (!toRemove.length) return;

    wx.showModal({
      title: '提示',
      content: '确定删除选中的 ' + toRemove.length + ' 个收藏？',
      confirmColor: '#dc2626',
      success: function (r) {
        if (!r.confirm) return;
        wx.showLoading({ title: '删除中...' });
        var promises = toRemove.map(function (it) {
          return api.favorite.remove({ item_id: it.realId });
        });
        Promise.all(promises).then(function () {
          wx.hideLoading();
          wx.showToast({ title: '删除成功', icon: 'success' });
          self.setData({ editing: false });
          self.load();
        }).catch(function () {
          wx.hideLoading();
          self.load();
        });
      },
    });
  },
});
