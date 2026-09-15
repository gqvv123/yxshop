const api = require('../../../api/index');
const auth = require('../../../utils/auth');

Page({
  data: {
    statusBarHeight: 20,
    list: [],
    picker: false,
    loading: true,
  },

  onLoad(options) {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.setData({ picker: options.picker == 1 });
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
    api.address.getList().then(function (res) {
      var list = Array.isArray(res) ? res : (res && res.data) || [];
      self.setData({ list: list });
    }).catch(function () {
      self.setData({ list: [] });
    }).then(function () {
      self.setData({ loading: false });
    });
  },

  onAdd() {
    wx.navigateTo({ url: '/pages/address/edit/edit' });
  },

  onEdit(e) {
    var id = e.currentTarget.dataset.id;
    wx.navigateTo({ url: '/pages/address/edit/edit?id=' + id });
  },

  onDelete(e) {
    var self = this;
    var id = e.currentTarget.dataset.id;
    wx.showModal({
      title: '提示',
      content: '确定删除该地址？',
      confirmColor: '#dc2626',
      success: function (r) {
        if (!r.confirm) return;
        api.address.delete(id).then(function () {
          self.load();
        }).catch(function () {});
      },
    });
  },

  onSetDefault(e) {
    var self = this;
    var id = e.currentTarget.dataset.id;
    api.address.setDefault(id).then(function () {
      self.load();
    }).catch(function () {});
  },

  onPick(e) {
    if (!this.data.picker) return;
    var raw = e.currentTarget.dataset.item || {};
    // safeStr：确保所有字段为字符串，剥离关联对象
    function ss(v) { return typeof v === 'string' ? v : ''; }
    var clean = {
      id: raw.id,
      name: ss(raw.name) || ss(raw.consignee),
      phone: ss(raw.phone) || ss(raw.mobile),
      province: ss(raw.province_name) || ss(raw.province),
      city: ss(raw.city_name) || ss(raw.city),
      district: ss(raw.district_name) || ss(raw.district),
      province_name: ss(raw.province_name),
      city_name: ss(raw.city_name),
      district_name: ss(raw.district_name),
      detail: ss(raw.detail) || ss(raw.address),
      is_default: raw.is_default || 0,
    };
    wx.setStorageSync('yxshop:pickedAddress', clean);
    wx.navigateBack();
  },
});
