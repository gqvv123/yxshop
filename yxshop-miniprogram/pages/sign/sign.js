const api = require('../../api/index');
const auth = require('../../utils/auth');

Page({
  data: {
    statusBarHeight: 20,
    signedToday: false,
    continuousDays: 0,
    totalDays: 0,
    signPoints: 5,
    signing: false,
    /** 日历 */
    year: 0,
    month: 0,
    weekDays: ['日', '一', '二', '三', '四', '五', '六'],
    calendarDays: [],
    signedDates: {},
    /** 规则 */
    rules: [
      { day: 1, points: 1 },
      { day: 2, points: 2 },
      { day: 3, points: 3 },
      { day: 5, points: 5 },
      { day: 7, points: 7 },
      { day: 15, points: 15 },
      { day: 30, points: 30 },
    ],
  },

  onLoad() {
    try {
      var sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    if (!auth.isLoggedIn()) {
      wx.showToast({ title: '请先登录', icon: 'none' });
      setTimeout(function () { wx.navigateBack(); }, 1500);
      return;
    }

    var now = new Date();
    this.setData({
      year: now.getFullYear(),
      month: now.getMonth(),
    });

    this.loadStatus();
    this.loadRecords();
  },

  onPullDownRefresh() {
    Promise.all([this.loadStatus(), this.loadRecords()]).catch(function () {}).finally(function () {
      wx.stopPullDownRefresh();
    });
  },

  /** 签到状态 */
  async loadStatus() {
    try {
      var res = await api.sign.getStatus();
      var status = (res && res.data) || res || {};
      this.setData({
        signedToday: !!status.signed_today,
        continuousDays: status.continuous_days || 0,
        totalDays: status.total_days || 0,
        signPoints: status.sign_points || 5,
      });
    } catch (e) {}
  },

  /** 签到记录 */
  async loadRecords() {
    try {
      var res = await api.sign.getRecords();
      var data = (res && res.data) || res || {};
      var records = data.records || data.list || data || [];
      var signedDates = {};
      if (Array.isArray(records)) {
        records.forEach(function (r) {
          var date = r.sign_date || r.date || r.created_at;
          if (date) {
            var d = date.substring(0, 10);
            signedDates[d] = true;
          }
        });
      }
      this.setData({ signedDates: signedDates });
      this.buildCalendar();
    } catch (e) {
      this.buildCalendar();
    }
  },

  /** 构建日历 */
  buildCalendar() {
    var year = this.data.year;
    var month = this.data.month;
    var firstDay = new Date(year, month, 1).getDay();
    var daysInMonth = new Date(year, month + 1, 0).getDate();
    var today = new Date();
    var todayStr = this._formatDate(today);

    var days = [];
    // 前置空白
    for (var i = 0; i < firstDay; i++) {
      days.push({ empty: true });
    }
    // 日期
    for (var d = 1; d <= daysInMonth; d++) {
      var dateStr = year + '-' + this._pad(month + 1) + '-' + this._pad(d);
      var isSigned = !!this.data.signedDates[dateStr];
      var isToday = dateStr === todayStr;
      var isFuture = new Date(year, month, d) > today;
      days.push({
        day: d,
        dateStr: dateStr,
        isSigned: isSigned,
        isToday: isToday,
        isFuture: isFuture,
      });
    }

    this.setData({ calendarDays: days });
  },

  _formatDate(d) {
    return d.getFullYear() + '-' + this._pad(d.getMonth() + 1) + '-' + this._pad(d.getDate());
  },

  _pad(n) {
    return n < 10 ? '0' + n : '' + n;
  },

  /** 上月 */
  onPrevMonth() {
    var m = this.data.month - 1;
    var y = this.data.year;
    if (m < 0) { m = 11; y--; }
    this.setData({ year: y, month: m });
    this.buildCalendar();
  },

  /** 下月 */
  onNextMonth() {
    var now = new Date();
    var y = this.data.year;
    var m = this.data.month + 1;
    if (m > 11) { m = 0; y++; }
    // 不能超过当前月
    if (y > now.getFullYear() || (y === now.getFullYear() && m > now.getMonth())) return;
    this.setData({ year: y, month: m });
    this.buildCalendar();
  },

  /** 执行签到 */
  async onSign() {
    if (this.data.signedToday || this.data.signing) return;
    this.setData({ signing: true });
    wx.showLoading({ title: '签到中...' });
    try {
      var res = await api.sign.doSign();
      wx.hideLoading();
      var data = (res && res.data) || res || {};
      var points = data.sign_points || data.points || 5;
      var continuous = data.sign_continuous || data.continuous_days || 1;
      wx.showToast({ title: '签到成功 +' + points + '积分', icon: 'success' });
      this.setData({
        signedToday: true,
        continuousDays: continuous,
        totalDays: this.data.totalDays + 1,
        signing: false,
      });
      // 更新签到日历
      var today = new Date();
      var todayStr = this._formatDate(today);
      var signedDates = this.data.signedDates;
      signedDates[todayStr] = true;
      this.setData({ signedDates: signedDates });
      this.buildCalendar();
    } catch (e) {
      wx.hideLoading();
      this.setData({ signing: false });
      wx.showToast({ title: (e && e.message) || '签到失败', icon: 'none' });
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
