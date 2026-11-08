Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    layout: 'one_big_two_small',
    cards: [],
  },
  observers: {
    props(val) {
      if (!val) return;
      this.setData({
        layout: val.layout || 'one_big_two_small',
        cards: val.cards || [],
      });
    },
  },
  methods: {
    onCardTap(e) {
      const idx = e.currentTarget.dataset.index;
      const card = this.data.cards[idx];
      if (!card || !card.link) return;
      if (/^\//.test(card.link)) {
        wx.navigateTo({ url: card.link });
      } else if (/^https?:/.test(card.link)) {
        wx.navigateTo({ url: '/pages/webview/webview?url=' + encodeURIComponent(card.link) });
      }
    },
  },
});
