Component({
  properties: {
    props: { type: Object, value: {} },
    theme: { type: Object, value: {} },
  },
  data: {
    content: '',
    scrollable: true,
    icon: 'horn',
    textColor: '#1e40af',
    backgroundColor: 'rgba(37,99,235,0.06)',
    hero: false,
  },
  observers: {
    props(val) {
      if (!val) return;
      const isHero = val._hero === true;
      this.setData({
        content: val.content || '',
        scrollable: val.scrollable !== false,
        icon: val.icon || 'horn',
        textColor: isHero ? '#fff' : (val.text_color || '#333333'),
        backgroundColor: isHero ? 'rgba(255,255,255,0.12)' : (val.background_color || '#FFF7E6'),
        hero: isHero,
      });
    },
  },
});