const api = require('../../../api/index');

Page({
  data: {
    statusBarHeight: 20,
    tasks: [],
    loading: true,
    points: 0,
  },

  onLoad() {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.loadTasks();
    this.loadPoints();
  },

  onPullDownRefresh() {
    Promise.all([this.loadTasks(), this.loadPoints()]).catch(function () {}).finally(function () {
      wx.stopPullDownRefresh();
    });
  },

  async loadTasks() {
    this.setData({ loading: true });
    try {
      var res = await api.task.getList();
      var data = res || {};
      var tasks = data.list || data.tasks || data || [];
      if (!Array.isArray(tasks)) tasks = [];
      tasks = tasks.map(function (t) {
        return Object.assign({}, t, {
          statusText: _taskStatus(t),
          statusClass: _taskStatusClass(t),
          iconType: _taskIcon(t.code || t.type || t.task_type),
        });
      });
      this.setData({ tasks: tasks, loading: false });
    } catch (e) {
      this.setData({ tasks: [], loading: false });
    }
  },

  async loadPoints() {
    try {
      var res = await api.point.getIntegralStats();
      var data = res || {};
      this.setData({ points: data.balance || data.total || data.points || 0 });
    } catch (e) {}
  },

  async onClaim(e) {
    var taskId = e.currentTarget.dataset.id;
    var canClaim = e.currentTarget.dataset.canClaim;
    // 微信小程序 dataset 会将布尔值转为字符串 'true'/'false'，需特殊处理
    if (!canClaim || canClaim === 'false') return;

    wx.showLoading({ title: '领取中...' });
    try {
      await api.task.claim(taskId);
      wx.hideLoading();
      wx.showToast({ title: '领取成功', icon: 'success' });
      this.loadTasks();
      this.loadPoints();
    } catch (e) {
      wx.hideLoading();
      wx.showToast({ title: (e && e.message) || '领取失败', icon: 'none' });
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
});

function _taskStatus(t) {
  if (!t) return '去完成';
  if (t.status_text) return t.status_text;
  if (t.can_claim) return '可领取';
  if (t.status === 2) return '已领取';
  if (t.status === 3) return '已完成';
  return '去完成';
}
function _taskStatusClass(t) {
  if (!t) return 'todo';
  if (t.can_claim) return 'claimable';
  var s = t.status;
  if (s === 2 || s === 3) return 'done';
  return 'todo';
}
function _taskIcon(type) {
  var map = {
    sign: 'check-circle',
    complete_profile: 'user-circle',
    profile: 'user-circle',
    first_order: 'shopping-cart',
    shop: 'shopping-cart',
    first_review: 'star',
    comment: 'star',
    review: 'star',
    share_product: 'gift',
    share: 'gift',
    invite: 'users',
  };
  return map[type] || 'zap';
}
