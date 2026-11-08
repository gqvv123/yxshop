Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    placeholder: '搜索商品',
    style: 'rounded',
    showScan: false,
    showMsg: false,
    msgDot: false,
    hero: false,
    showCity: false,
    cityName: '',
  },
  observers: {
    props(val) {
      if (!val) return;
      this.setData({
        placeholder: val.placeholder || '搜索商品',
        style: val.style || 'rounded',
        showScan: val.show_scan !== false,
        showMsg: val.show_msg === true,
        msgDot: val.msg_dot === true,
        hero: val._hero === true,
        showCity: val.show_city === true,
        cityName: val.city_name || '定位中',
      });
    },
  },
  methods: {
    onTap() {
      wx.navigateTo({ url: '/pages/search/search' });
    },
    onCityTap() {
      wx.chooseLocation({
        success: (res) => {
          var name = (res.name || res.address || '').slice(0, 8);
          if (name) this.setData({ cityName: name });
        },
        fail() {
          wx.showToast({ title: '请授权位置信息', icon: 'none' });
        },
      });
    },
    onScanTap() {
      wx.scanCode({
        success(res) {
          const result = res.result || '';
          if (/^https?:/.test(result)) {
            wx.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(result) });
          }
        },
        fail() {},
      });
    },
    onMsgTap() {
      wx.navigateTo({ url: '/pages/notification/notification' });
    },
  },
});