Component({
  options: { addGlobalClass: true },
  properties: {
    icon: { type: String, value: 'package' },
    emoji: { type: String, value: '' },
    text: { type: String, value: '暂无数据' },
    subText: { type: String, value: '' },
    actionText: { type: String, value: '' },
  },
  methods: {
    onAction() { this.triggerEvent('action'); },
  },
});
