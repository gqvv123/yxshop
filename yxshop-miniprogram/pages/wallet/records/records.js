/**
 * 资金记录
 * 对应管理端「系统设置 → 充值套餐」「用户管理 → 余额日志」
 * 三个 Tab：充值记录 / 提现记录 / 余额流水
 */
const api = require('../../../api/index');
const auth = require('../../../utils/auth');
const { fmtPrice, fmtDate } = require('../../../utils/format');

const TABS = [
  { key: 'recharge', label: '充值记录' },
  { key: 'withdraw', label: '提现记录' },
  { key: 'money', label: '余额流水' },
];

Page({
  data: {
    statusBarHeight: 20,
    tabs: TABS,
    activeTab: 0,
    list: [],
    page: 1,
    pageSize: 15,
    hasMore: true,
    loading: false,
    loadError: '',
  },

  onLoad(options) {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}

    if (!auth.isLoggedIn()) {
      wx.redirectTo({ url: '/pages/login/login' });
      return;
    }
    const initTab = Math.max(0, TABS.findIndex((t) => t.key === (options && options.tab)));
    this.setData({ activeTab: initTab });
    this.load(true);
  },

  onPullDownRefresh() {
    this.load(true).then(() => wx.stopPullDownRefresh());
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.load(false);
    }
  },

  onTabChange(e) {
    const idx = Number(e.currentTarget.dataset.idx || 0);
    if (idx === this.data.activeTab) return;
    this.setData({ activeTab: idx, list: [], page: 1, hasMore: true });
    this.load(true);
  },

  async load(reset) {
    if (this.data.loading) return;
    this.setData({ loading: true, loadError: '' });

    const key = TABS[this.data.activeTab].key;
    const page = reset ? 1 : this.data.page;

    try {
      let res;
      if (key === 'recharge') {
        res = await api.balance.getRechargeRecords({ page, page_size: this.data.pageSize });
      } else if (key === 'withdraw') {
        res = await api.withdraw.getRecords({ page, page_size: this.data.pageSize });
      } else {
        res = await api.balance.getMoneyLog({ page, page_size: this.data.pageSize });
      }

      const data = res || {};
      const raw = data.list || data.data || (Array.isArray(res) ? res : []);
      const rows = raw.map((r) => this.formatRow(r, key));

      this.setData({
        list: reset ? rows : this.data.list.concat(rows),
        page: page + 1,
        hasMore: rows.length >= this.data.pageSize,
        loading: false,
      });
    } catch (e) {
      this.setData({
        loading: false,
        hasMore: false,
        loadError: (e && e.message) || '加载失败',
      });
    }
  },

  /** 三种记录字段不同，统一成 {title, sub, amount, amountText, positive, statusText, timeText} */
  formatRow(r, key) {
    if (key === 'recharge') {
      const amount = Number(r.recharge_amount || 0);
      const bonus = Number(r.bonus_amount || 0);
      return {
        id: r.id,
        title: r.pay_status_text === '已支付' ? '充值成功' : '充值待支付',
        sub: bonus > 0 ? `含赠送 ¥${fmtPrice(bonus)}` : (r.order_no || ''),
        amount: amount,
        amountText: `+¥${fmtPrice(amount)}`,
        positive: true,
        statusText: r.pay_status_text || '',
        statusClass: r.pay_status_text === '已支付' ? 'ok' : 'pending',
        timeText: this.formatTime(r.created_at),
      };
    }

    if (key === 'withdraw') {
      const amount = Number(r.amount || 0);
      const actual = Number(r.actual_amount || 0);
      return {
        id: r.id,
        title: `提现到${r.type_text || '账户'}`,
        sub: Number(r.fee || 0) > 0 ? `手续费 ¥${fmtPrice(r.fee)}，到账 ¥${fmtPrice(actual)}` : (r.remark || ''),
        amount: -amount,
        amountText: `-¥${fmtPrice(amount)}`,
        positive: false,
        statusText: r.status_text || '',
        statusClass: Number(r.status) === 1 ? 'ok' : 'pending',
        timeText: this.formatTime(r.created_at),
      };
    }

    const change = Number(r.change_amount || r.amount || r.money || 0);
    const isIn = change >= 0;
    return {
      id: r.id,
      title: r.change_type_text || r.type_text || r.remark || '余额变动',
      sub: r.remark || '',
      amount: change,
      amountText: `${isIn ? '+' : '-'}¥${fmtPrice(Math.abs(change))}`,
      positive: isIn,
      statusText: '',
      statusClass: '',
      timeText: this.formatTime(r.created_at || r.create_time),
      balanceText: r.balance !== undefined ? `余额 ¥${fmtPrice(r.balance)}` : '',
    };
  },

  /** 后端 created_at 可能是 int 时间戳，也可能是已格式化字符串 */
  formatTime(v) {
    if (!v) return '';
    if (typeof v === 'number' || /^\d{10}$/.test(String(v))) {
      return fmtDate(Number(v), true);
    }
    return String(v);
  },

  onBack() {
    var pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.navigateTo({ url: '/pages/wallet/wallet' });
    }
  },

  onGoRecharge() {
    wx.navigateTo({ url: '/pages/wallet/wallet' });
  },
});
