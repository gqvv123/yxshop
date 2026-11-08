Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    style: 'solid',
    bg1: '#2563eb',
    bg2: '#3b82f6',
    icon: '',
    iconBg: '',
    title: '',
    subtitle: '',
    btnText: '',
    btnLink: '',
    showPulse: false,
  },
  observers: {
    props(val) {
      if (!val) return;
      this.setData({
        style: val.style || 'solid',
        bg1: val.bg_color1 || '#2563eb',
        bg2: val.bg_color2 || '#3b82f6',
        icon: val.icon || '',
        iconBg: val.icon_bg || '',
        title: val.title || '',
        subtitle: val.subtitle || '',
        btnText: val.btn_text || '',
        btnLink: val.btn_link || '',
        showPulse: val.show_pulse === true,
      });
    },
  },
  methods: {
    onBtnTap() {
      const link = this.data.btnLink;
      if (!link) return;
      if (/^\//.test(link)) {
        wx.navigateTo({ url: link });
      } else if (/^https?:/.test(link)) {
        wx.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(link) });
      }
    },
  },
});
