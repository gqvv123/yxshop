Component({
  properties: {
    visible: { type: Boolean, value: false },
  },

  data: {
    privacyContractName: '',
  },

  lifetimes: {
    attached() {
      if (wx.getPrivacySetting) {
        try {
          const res = wx.getPrivacySetting();
          this.setData({ privacyContractName: res.privacyContractName || '隐私保护指引' });
        } catch (e) {
          this.setData({ privacyContractName: '隐私保护指引' });
        }
      }
    },
  },

  methods: {
    onAgree() {
      this.triggerEvent('agree');
    },

    onDisagree() {
      this.triggerEvent('disagree');
    },

    onViewContract() {
      if (wx.openPrivacyContract) {
        wx.openPrivacyContract();
      }
    },
  },
});