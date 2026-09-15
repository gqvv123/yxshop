// pages/wallet/wallet.js
const api = require('../../api/index');

Page({
  data: {
    statusBarHeight: 20,
    /** 余额信息 */
    balance: '0.00',
    balanceInt: '0',
    balanceDec: '00',
    frozenAmount: '0.00',
    totalIncome: '0.00',
    totalExpense: '0.00',
    monthlyIncome: '0.00',
    /** 充值套餐 */
    packages: [],
    selectedPackageId: null,
    customAmount: '',
    recharging: false,
    /** 交易明细 */
    transactions: [],
    page: 1,
    pageSize: 20,
    hasMore: true,
    /** 筛选 */
    showFilter: false,
    currentFilter: 'all',
    filterText: '全部',
    filterOptions: [
      { value: 'all', label: '全部' },
      { value: 'income', label: '收入' },
      { value: 'expense', label: '支出' },
      { value: 'recharge', label: '充值' },
      { value: 'withdraw', label: '提现' },
      { value: 'consume', label: '消费' },
      { value: 'refund', label: '退款' },
    ],
    /** 提现弹窗 */
    showWithdraw: false,
    withdrawAmount: '',
    withdrawType: 3, // 3=微信, 2=支付宝, 1=银行卡
    withdrawAccount: '',
    withdrawAccountName: '',
    withdrawBankName: '',
    withdrawing: false,
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.loadAll();
  },

  onPullDownRefresh() {
    this.loadAll().finally(() => {
      wx.stopPullDownRefresh();
    });
  },

  async loadAll() {
    try {
      await Promise.all([
        this.loadBalance(),
        this.loadPackages(),
        this.loadTransactions(true),
      ]);
    } catch (e) {
      // 防止单个 promise reject 导致整个页面崩溃
      this.setData({ loading: false });
    }
  },

  // ===== 余额信息 =====

  async loadBalance() {
    try {
      const r = await api.balance.getBalance();
      if (!r) return;
      const balance = Number(r.balance || 0).toFixed(2);
      const parts = balance.split('.');
      this.setData({
        balance: balance,
        balanceInt: parts[0],
        balanceDec: parts[1],
        frozenAmount: Number(r.frozen || 0).toFixed(2),
        totalIncome: Number(r.total_income || 0).toFixed(2),
        totalExpense: Number(r.total_expense || 0).toFixed(2),
        monthlyIncome: Number(r.monthly_income || 0).toFixed(2),
      });
    } catch (e) { /* ignore */ }

    // 尝试加载收支统计
    try {
      const stats = await api.balance.getMoneyStats();
      if (stats) {
        this.setData({
          totalIncome: Number(stats.total_income || 0).toFixed(2),
          totalExpense: Number(stats.total_expense || 0).toFixed(2),
          monthlyIncome: Number(stats.monthly_income || 0).toFixed(2),
        });
      }
    } catch (e) { /* ignore */ }
  },

  // ===== 充值套餐 =====

  async loadPackages() {
    try {
      const r = await api.balance.getPackages();
      if (!r || !Array.isArray(r)) return;
      const packages = r.map((item) => ({
        id: item.id,
        amount: Number(item.amount || 0),
        gift_text: item.gift_amount ? '送' + Number(item.gift_amount) + '元' : (item.gift_text || ''),
        is_recommended: !!item.is_recommended,
      }));
      // 默认选中推荐项
      const recommended = packages.find((p) => p.is_recommended);
      this.setData({
        packages,
        selectedPackageId: recommended ? recommended.id : (packages.length > 0 ? packages[0].id : null),
      });
    } catch (e) {
      // 使用默认套餐
      this._loadDefaultPackages();
    }
  },

  _loadDefaultPackages() {
    const defaults = [
      { id: 1, amount: 100, gift_text: '送2元', is_recommended: false },
      { id: 2, amount: 500, gift_text: '送15元', is_recommended: true },
      { id: 3, amount: 1000, gift_text: '送50元', is_recommended: false },
      { id: 4, amount: 2000, gift_text: '送120元', is_recommended: false },
      { id: 5, amount: 5000, gift_text: '送350元', is_recommended: false },
    ];
    this.setData({
      packages: defaults,
      selectedPackageId: 2,
    });
  },

  onSelectPackage(e) {
    const id = Number(e.currentTarget.dataset.id);
    this.setData({ selectedPackageId: id });
  },

  onCustomInput(e) {
    this.setData({ customAmount: e.detail.value });
  },

  async onConfirmRecharge() {
    if (this.data.recharging) return;

    const { selectedPackageId, packages, customAmount } = this.data;

    let packageId = null;
    let amount = 0;

    if (selectedPackageId === -1) {
      // 自定义金额
      amount = parseFloat(customAmount);
      if (!amount || amount <= 0) {
        return wx.showToast({ title: '请输入有效金额', icon: 'none' });
      }
      if (amount < 1) {
        return wx.showToast({ title: '最低充值 1 元', icon: 'none' });
      }
    } else {
      const pkg = packages.find((p) => p.id === selectedPackageId);
      if (!pkg) {
        return wx.showToast({ title: '请选择充值套餐', icon: 'none' });
      }
      packageId = pkg.id;
      amount = pkg.amount;
    }

    this.setData({ recharging: true });
    try {
      let res;
      if (packageId) {
        res = await api.balance.createRecharge(packageId);
      } else {
        // 自定义金额 — 尝试传 amount
        res = await api.balance.createRecharge({ amount: amount });
      }

      // 如果返回了支付参数，调起微信支付
      if (res && res.pay_params) {
        await this._callWxPay(res.pay_params);
      } else if (res && res.payment) {
        await this._callWxPay(res.payment);
      }

      wx.showToast({ title: '充值成功', icon: 'success' });
      this.loadBalance();
      this.loadTransactions(true);
    } catch (e) {
      wx.showToast({ title: e.message || '充值失败', icon: 'none' });
    } finally {
      this.setData({ recharging: false });
    }
  },

  _callWxPay(params) {
    return new Promise((resolve, reject) => {
      wx.requestPayment({
        timeStamp: params.timeStamp,
        nonceStr: params.nonceStr,
        package: params.package,
        signType: params.signType || 'MD5',
        paySign: params.paySign,
        success: resolve,
        fail: reject,
      });
    });
  },

  // ===== 交易明细 =====

  async loadTransactions(reset) {
    if (reset) {
      this.setData({ page: 1, hasMore: true, transactions: [] });
    }
    if (!this.data.hasMore && !reset) return;

    try {
      const params = {
        page: this.data.page,
        page_size: this.data.pageSize,
      };
      if (this.data.currentFilter !== 'all') {
        params.type = this.data.currentFilter;
      }

      const r = await api.balance.getMoneyLog(params);
      if (!r) return;

      const list = r.list || r.data || r.records || [];
      const total = r.total || r.total_count || 0;

      const formatted = list.map((item) => this._formatTransaction(item));
      const all = reset ? formatted : this.data.transactions.concat(formatted);

      this.setData({
        transactions: all,
        hasMore: all.length < total,
        page: reset ? 2 : this.data.page + 1,
      });
    } catch (e) { /* ignore */ }
  },

  _formatTransaction(item) {
    const type = item.type || (item.amount > 0 ? 'income' : 'expense');
    const amount = Math.abs(Number(item.amount || 0)).toFixed(2);

    // 图标映射
    let icon = 'arrow-down';
    const title = item.title || item.description || item.remark || '交易';
    if (title.indexOf('充值') >= 0) icon = 'arrow-down';
    else if (title.indexOf('提现') >= 0) icon = 'arrow-up';
    else if (title.indexOf('消费') >= 0 || title.indexOf('订单') >= 0) icon = 'arrow-up';
    else if (title.indexOf('退款') >= 0) icon = 'recycle';
    else if (title.indexOf('赠送') >= 0 || title.indexOf('收益') >= 0) icon = 'gift';

    // 状态映射
    let statusType = 'success';
    let statusText = '已完成';
    if (item.status === 0 || item.status === 'pending') {
      statusType = 'pending';
      statusText = '处理中';
    } else if (item.status === 2 || item.status === 'failed') {
      statusType = 'pending';
      statusText = '已失败';
    } else if (title.indexOf('退款') >= 0 && (item.status === 0 || item.status === 'pending')) {
      statusType = 'pending';
      statusText = '退款中';
    }

    return {
      id: item.id,
      type: type,
      icon: icon,
      title: title,
      amount: amount,
      created_at: item.created_at || item.created_time || '',
      status_type: statusType,
      status_text: statusText,
    };
  },

  loadMore() {
    this.loadTransactions(false);
  },

  // ===== 筛选 =====

  onToggleFilter() {
    this.setData({ showFilter: !this.data.showFilter });
  },

  onSelectFilter(e) {
    const value = e.currentTarget.dataset.value;
    const option = this.data.filterOptions.find((o) => o.value === value);
    this.setData({
      currentFilter: value,
      filterText: option ? option.label : '全部',
      showFilter: false,
    });
    this.loadTransactions(true);
  },

  // ===== 操作 =====

  onBack() {
    wx.navigateBack();
  },

  onRecharge() {
    // 滚动到充值套餐区域
    wx.pageScrollTo({
      scrollTop: 600,
      duration: 300,
    });
  },

  // ===== 提现 =====

  onWithdraw() {
    this.setData({ showWithdraw: true, withdrawAmount: '', withdrawType: 3, withdrawAccount: '', withdrawAccountName: '', withdrawBankName: '' });
  },

  hideWithdraw() {
    this.setData({ showWithdraw: false });
  },

  noop() {},

  onWithdrawTypeChange(e) {
    var type = parseInt(e.currentTarget.dataset.type);
    this.setData({ withdrawType: type });
  },

  onWithdrawAmountInput(e) {
    this.setData({ withdrawAmount: e.detail.value });
  },

  onWithdrawAll() {
    this.setData({ withdrawAmount: this.data.balance });
  },

  async onConfirmWithdraw() {
    if (this.data.withdrawing) return;
    var amount = parseFloat(this.data.withdrawAmount);
    if (!amount || amount <= 0) {
      return wx.showToast({ title: '请输入有效金额', icon: 'none' });
    }
    if (amount < 10) {
      return wx.showToast({ title: '最低提现 10 元', icon: 'none' });
    }
    if (amount > parseFloat(this.data.balance)) {
      return wx.showToast({ title: '余额不足', icon: 'none' });
    }

    var type = this.data.withdrawType;
    var data = {
      amount: amount,
      withdraw_type: type,
      account: '',
      account_name: '',
    };

    if (type === 3) {
      // 微信提现 — account 留空，后端用 openid
      data.account = 'wechat';
    } else if (type === 2) {
      // 支付宝
      if (!this.data.withdrawAccount) {
        return wx.showToast({ title: '请输入支付宝账号', icon: 'none' });
      }
      data.account = this.data.withdrawAccount;
      data.account_name = this.data.withdrawAccountName || '';
    } else if (type === 1) {
      // 银行卡
      if (!this.data.withdrawAccount) {
        return wx.showToast({ title: '请输入银行卡号', icon: 'none' });
      }
      if (!this.data.withdrawAccountName) {
        return wx.showToast({ title: '请输入开户人姓名', icon: 'none' });
      }
      data.account = this.data.withdrawAccount;
      data.account_name = this.data.withdrawAccountName;
      data.bank_name = this.data.withdrawBankName || '';
    }

    this.setData({ withdrawing: true });
    try {
      await api.withdraw.apply(data);
      wx.showToast({ title: '提现申请已提交', icon: 'success' });
      this.setData({ showWithdraw: false });
      this.loadBalance();
      this.loadTransactions(true);
    } catch (e) {
      wx.showToast({ title: (e && e.message) || '提现失败', icon: 'none' });
    } finally {
      this.setData({ withdrawing: false });
    }
  },

  onWithdrawAccountInput(e) {
    this.setData({ withdrawAccount: e.detail.value });
  },

  onWithdrawAccountNameInput(e) {
    this.setData({ withdrawAccountName: e.detail.value });
  },

  onWithdrawBankNameInput(e) {
    this.setData({ withdrawBankName: e.detail.value });
  },

  onViewBill() {
    // 滚动到交易明细区域
    wx.pageScrollTo({
      scrollTop: 9999,
      duration: 300,
    });
  },

  onBankCard() {
    wx.showModal({
      title: '银行卡提现',
      content: '进行提现时，选择「银行卡」方式即可填写银行卡信息。\n\n目前支持的提现方式：\n· 微信钱包（推荐）\n· 支付宝转账\n· 银行卡转账',
      confirmText: '去提现',
      confirmColor: '#2563eb',
      cancelText: '知道了',
      success: (res) => {
        if (res.confirm) {
          this.onWithdraw();
        }
      },
    });
  },

  onSecurity() {
    wx.navigateTo({ url: '/pages/profile/security/security' });
  },
});
