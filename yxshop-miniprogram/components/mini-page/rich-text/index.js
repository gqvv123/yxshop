Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    content: '',
  },
  observers: {
    props(val) {
      if (!val) return;
      this.setData({ content: val.content || '' });
    },
  },
});