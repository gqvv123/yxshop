const api = require('../../api/index');
const auth = require('../../utils/auth');
const imageUtil = require('../../utils/image');

Page({
  data: {
    statusBarHeight: 20,
    points: 0,
    categories: ['全部', '数码电子', '生活日用', '食品饮料', '优惠券', '虚拟服务'],
    activeCate: 0,
    list: [],
    page: 1,
    hasMore: true,
    loading: false,
  },

  onLoad() {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    this.loadList();
    if (auth.isLoggedIn()) {
      this.loadPoints();
    }
  },

  /** 加载用户积分 */
  loadPoints() {
    var self = this;
    api.user.getInfo().then(function (u) {
      var info = (u && u.data) || u || {};
      self.setData({ points: info.integral || info.points || info.score || 0 });
    }).catch(function () {});
  },

  /** 加载积分商品列表 */
  loadList() {
    var self = this;
    if (self.data.loading || !self.data.hasMore) return;
    self.setData({ loading: true });

    api.pointItem.getList({ page: self.data.page, page_size: 20 })
      .then(function (r) {
        var result = (r && r.data) || r || {};
        // 兼容分页结构 { list: [...] } 或直接数组
        var rawItems = Array.isArray(result) ? result : (result.list || result.data || []);
        var items = rawItems.map(function (item) {
          return Object.assign({}, item, {
            coverUrl: imageUtil.getItemCover(item),
            total_sales: (item.total_sales || 0) + (item.initial_sales || 0),
          });
        });
        var list = self.data.page === 1 ? items : self.data.list.concat(items);
        self.setData({
          list: list,
          hasMore: items.length >= 20,
          page: self.data.page + 1,
          loading: false,
        });
      })
      .catch(function () {
        self.setData({ list: [], loading: false });
      });
  },

  /** 分类切换 */
  onCateTap(e) {
    var idx = Number(e.currentTarget.dataset.idx);
    if (idx === this.data.activeCate) return;
    this.setData({ activeCate: idx, list: [], page: 1, hasMore: true });
    this.loadList();
  },

  onReachBottom() {
    this.loadList();
  },

  /** 返回 */
  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/home/home' });
    }
  },

  /** 兑换规则 */
  onShowRules() {
    wx.showModal({
      title: '积分兑换规则',
      content: '1. 积分可通过签到、购物、评价等方式获取；\n2. 积分商品不支持退换货；\n3. 部分商品需积分+现金兑换；\n4. 积分每年12月31日清零，请及时使用；\n5. 实物商品兑换后将在3个工作日内发货；\n6. 虚拟商品兑换后即时发放。',
      showCancel: false,
      confirmText: '知道了',
      confirmColor: '#6b21a8',
    });
  },

  /** 点击商品跳详情 */
  onItemTap(e) {
    var id = e.currentTarget.dataset.id;
    if (id) {
      wx.navigateTo({ url: '/pages/item-detail/item-detail?id=' + id });
    }
  },

  /** 兑换积分商品 */
  onExchange(e) {
    var self = this;
    var id = e.currentTarget.dataset.id;
    var points = Number(e.currentTarget.dataset.points || 0);
    var stock = Number(e.currentTarget.dataset.stock || 0);
    var name = e.currentTarget.dataset.name || '';

    if (stock <= 0) {
      wx.showToast({ title: '库存不足', icon: 'none' });
      return;
    }
    if (points > self.data.points) {
      wx.showToast({ title: '积分不足', icon: 'none' });
      return;
    }

    wx.showModal({
      title: '确认兑换',
      content: '是否使用 ' + points + ' 积分兑换「' + name + '」？',
      confirmText: '确认兑换',
      confirmColor: '#6b21a8',
      success: function (res) {
        if (!res.confirm) return;
        wx.showLoading({ title: '兑换中...' });
        api.pointItem.exchange({ item_id: id })
          .then(function () {
            wx.hideLoading();
            wx.showToast({ title: '兑换成功', icon: 'success' });
            // 刷新积分和列表
            self.setData({ list: [], page: 1, hasMore: true });
            self.loadPoints();
            self.loadList();
          })
          .catch(function (err) {
            wx.hideLoading();
            wx.showToast({
              title: (err && err.message) || '兑换失败',
              icon: 'none',
            });
          });
      },
    });
  },

  /** 赚积分任务入口 */
  onTaskTap(e) {
    var type = e.currentTarget.dataset.type;
    switch (type) {
      case 'sign':
        wx.navigateTo({ url: '/pages/sign/sign' });
        break;
      case 'shop':
        wx.switchTab({ url: '/pages/home/home' });
        break;
      case 'comment':
        wx.navigateTo({ url: '/pages/order/list/list' });
        break;
    }
  },


  onGoTasks() {
    wx.navigateTo({ url: '/pages/point-exchange/tasks/tasks' });
  },

  onGoIntegralLog() {
    wx.navigateTo({ url: '/pages/point-exchange/integral-log/integral-log' });
  },

  onGoRecords() {
    wx.navigateTo({ url: '/pages/point-exchange/records/records' });
  },
});
