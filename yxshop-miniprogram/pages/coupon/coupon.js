const api = require('../../api/index');
const auth = require('../../utils/auth');

/** 颜色常量 → CSS class 映射（对应 Coupon::COLOR_* 常量） */
var COLOR_MAP = { 10: 'blue', 20: 'red', 30: 'purple', 40: 'gold' };

function getColorClass(color) {
  return COLOR_MAP[color] || 'blue';
}

/** 装饰优惠券项，补充 colorClass */
function decorateCoupon(item) {
  return Object.assign({}, item, { colorClass: getColorClass(item.color) });
}

/** 计算最高可省金额（仅满减券有固定面额） */
function calcSavings(list) {
  return list.reduce(function (sum, item) {
    if (item.type === 1) return sum + Number(item.discount_amount || 0);
    return sum;
  }, 0);
}

Page({
  data: {
    statusBarHeight: 20,
    activeTab: 0,
    tabKeys: ['unused', 'used', 'expired'],
    list: [],
    page: 1,
    hasMore: true,
    loading: false,
    stats: { unused: 0, used: 0, expired: 0, total: 0 },
    maxSavings: 0,
    claimList: [],
  },

  onLoad() {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    if (!auth.isLoggedIn()) {
      wx.navigateTo({ url: '/pages/login/login' });
      return;
    }
    this.loadAll();
  },

  /** 并行加载统计、列表、领券中心 */
  loadAll() {
    var self = this;
    return Promise.all([
      self.loadStats(),
      self.loadList(),
      self.loadClaimList(),
    ]);
  },

  /** 加载统计 */
  loadStats() {
    var self = this;
    return api.coupon.getStats().then(function (r) {
      var stats = (r && r.data) || r || {};
      self.setData({ stats: stats });
    }).catch(function () {});
  },

  /** 加载我的优惠券列表 */
  loadList() {
    var self = this;
    if (self.data.loading || !self.data.hasMore) return Promise.resolve();
    self.setData({ loading: true });

    var status = self.data.tabKeys[self.data.activeTab];
    return api.coupon.getMyList({ status: status, page: self.data.page, page_size: 20 })
      .then(function (r) {
        var result = (r && r.data) || r || {};
        var items = (result.list || []).map(decorateCoupon);
        var list = self.data.page === 1 ? items : self.data.list.concat(items);
        var update = {
          list: list,
          hasMore: items.length >= 20,
          page: self.data.page + 1,
          loading: false,
        };
        // 可使用 tab 下计算最高可省
        if (self.data.activeTab === 0) {
          update.maxSavings = Math.round(calcSavings(list));
        }
        self.setData(update);
      })
      .catch(function () {
        self.setData({ list: [], loading: false });
      });
  },

  /** 加载领券中心 */
  loadClaimList() {
    var self = this;
    return api.coupon.getAvailable().then(function (r) {
      var items = ((r && r.data) || r || []).map(function (item) {
        return Object.assign({}, item, { colorClass: getColorClass(item.color) });
      });
      self.setData({ claimList: items });
    }).catch(function () {
      self.setData({ claimList: [] });
    });
  },

  /** Tab 切换 */
  onTabChange(e) {
    var idx = Number(e.currentTarget.dataset.idx);
    if (idx === this.data.activeTab) return;
    this.setData({
      activeTab: idx,
      list: [],
      page: 1,
      hasMore: true,
      maxSavings: 0,
    });
    this.loadList();
  },

  onReachBottom() {
    this.loadList();
  },

  /** 返回上一页 */
  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/home/home' });
    }
  },

  /** 使用规则 */
  onShowRules() {
    wx.showModal({
      title: '优惠券使用规则',
      content: '1. 优惠券仅限本平台使用；\n2. 每笔订单仅可使用一张优惠券；\n3. 优惠券不可叠加使用；\n4. 订单金额需满足优惠券使用门槛；\n5. 优惠券过期后不可使用；\n6. 退货时优惠券不退回。',
      showCancel: false,
      confirmText: '知道了',
      confirmColor: '#2563eb',
    });
  },

  /** 滚动到领券中心 */
  onGoClaim() {
    var self = this;
    if (!self.data.claimList.length) {
      wx.showToast({ title: '暂无可领优惠券', icon: 'none' });
      return;
    }
    // 领券中心仅在「可使用」tab 渲染，需先切换
    if (self.data.activeTab !== 0) {
      self.setData({ activeTab: 0, list: [], page: 1, hasMore: true, maxSavings: 0 });
      self.loadList();
      setTimeout(function () {
        wx.pageScrollTo({ selector: '#claim-section', duration: 300 });
      }, 400);
    } else {
      wx.pageScrollTo({ selector: '#claim-section', duration: 300 });
    }
  },

  /** 去使用优惠券 */
  onUseCoupon(e) {
    var status = Number(e.currentTarget.dataset.status);
    if (status !== 0) return;
    wx.switchTab({ url: '/pages/home/home' });
  },

  /** 领取优惠券 */
  onClaimCoupon(e) {
    var self = this;
    var id = Number(e.currentTarget.dataset.id);
    var received = Number(e.currentTarget.dataset.received || 0);
    var limit = Number(e.currentTarget.dataset.limit || 1);
    if (!id) return;

    if (received >= limit) {
      wx.showToast({ title: '已领取该优惠券', icon: 'none' });
      return;
    }

    wx.showLoading({ title: '领取中...' });
    api.coupon.claim(id)
      .then(function () {
        wx.hideLoading();
        wx.showToast({ title: '领取成功', icon: 'success' });
        // 刷新全部数据
        self.setData({ list: [], page: 1, hasMore: true });
        self.loadAll();
      })
      .catch(function (err) {
        wx.hideLoading();
        wx.showToast({
          title: (err && err.message) || '领取失败',
          icon: 'none',
        });
      });
  },
});
