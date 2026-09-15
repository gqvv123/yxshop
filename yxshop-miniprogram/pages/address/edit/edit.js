const api = require('../../../api/index');

/** 确保值为字符串，过滤掉对象/undefined/null 等 */
function safeStr(v) {
  return typeof v === 'string' ? v : '';
}

Page({
  data: {
    statusBarHeight: 20,
    id: 0,
    form: {
      name: '',
      phone: '',
      province: '',
      city: '',
      district: '',
      detail: '',
      is_default: 0,
    },
    submitting: false,
  },

  onLoad(options) {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    if (options.id) {
      this.setData({ id: options.id || '' });
      this.loadDetail();
    }
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  loadDetail() {
    var self = this;
    api.address.getList().then(function (res) {
      var arr = Array.isArray(res) ? res : (res && res.data) || [];
      var cur = arr.find(function (x) { return (x.id || x.address_id) == self.data.id; });
      if (cur) {
        // API 返回的 province/city/district 可能是关联对象（非字符串），
        // province_name 等字段也可能意外为对象，统一用 safeStr 过滤
        self.setData({
          form: {
            name: safeStr(cur.name) || safeStr(cur.consignee),
            phone: safeStr(cur.phone) || safeStr(cur.mobile),
            province: safeStr(cur.province_name) || safeStr(cur.province),
            city: safeStr(cur.city_name) || safeStr(cur.city),
            district: safeStr(cur.district_name) || safeStr(cur.district),
            detail: safeStr(cur.detail) || safeStr(cur.address),
            is_default: cur.is_default || 0,
          },
        });
      }
    }).catch(function () {});
  },

  onInput(e) {
    var field = e.currentTarget.dataset.field;
    this.setData({ ['form.' + field]: e.detail.value });
  },

  onRegionChange(e) {
    var val = e.detail.value || [];
    this.setData({
      'form.province': val[0] || '',
      'form.city': val[1] || '',
      'form.district': val[2] || '',
    });
  },

  onDefaultChange(e) {
    this.setData({ 'form.is_default': e.detail.value ? 1 : 0 });
  },

  onChooseWxAddress() {
    var self = this;
    wx.chooseAddress({
      success: function (res) {
        self.setData({
          'form.name': res.userName,
          'form.phone': res.telNumber,
          'form.province': res.provinceName,
          'form.city': res.cityName,
          'form.district': res.countyName,
          'form.detail': res.detailInfo,
        });
      },
      fail: function (err) {
        if (err && err.errMsg && err.errMsg.indexOf('privacy') > -1) {
          wx.showToast({ title: '需要同意隐私协议才能获取地址', icon: 'none' });
        }
      },
    });
  },

  onSubmit() {
    var self = this;
    if (self.data.submitting) return;
    var form = self.data.form;
    if (!form.name) {
      return wx.showToast({ title: '请输入收货人姓名', icon: 'none' });
    }
    if (!form.phone || !/^1\d{10}$/.test(form.phone)) {
      return wx.showToast({ title: '请输入正确的手机号', icon: 'none' });
    }
    if (!form.province) {
      return wx.showToast({ title: '请选择所在地区', icon: 'none' });
    }
    if (!form.detail) {
      return wx.showToast({ title: '请输入详细地址', icon: 'none' });
    }

    self.setData({ submitting: true });
    var promise;
    if (self.data.id) {
      promise = api.address.update(Object.assign({ id: self.data.id }, form));
    } else {
      promise = api.address.add(form);
    }
    promise.then(function () {
      wx.showToast({ title: '保存成功', icon: 'success' });
      setTimeout(function () { wx.navigateBack(); }, 800);
    }).catch(function (err) {
      wx.showToast({ title: (err && err.message) || '保存失败', icon: 'none' });
    }).then(function () {
      self.setData({ submitting: false });
    });
  },
});
