/**
 * 我的发票
 * 对应管理端「财务管理 → 发票管理」的用户侧能力：抬头管理 + 开票申请 + 开票记录
 */
const api = require('../../api/index');
const auth = require('../../utils/auth');

Page({
  data: {
    activeTab: 0,
    tabKeys: ['headers', 'records'],
    headers: [],
    records: [],
    page: 1,
    pageSize: 10,
    hasMore: true,
    loading: false,
    showForm: false,
    editing: null,
    form: { header_name: '', invoice_type: 1, tax_no: '', reg_address: '', reg_phone: '', bank_name: '', bank_account: '', is_default: 0 },
  },

  onLoad() {
    if (!auth.isLoggedIn()) {
      wx.navigateTo({ url: '/pages/login/login' });
      return;
    }
    this.loadData(true);
  },

  onShow() {
    if (auth.isLoggedIn() && this.data.headers.length) {
      this.loadData(true);
    }
  },

  onReachBottom() {
    if (this.data.hasMore && !this.data.loading) {
      this.loadData(false);
    }
  },

  /** 按当前 tab 加载 */
  loadData(reset) {
    if (this.data.activeTab === 0) {
      this.loadHeaders();
    } else {
      this.loadRecords(reset);
    }
  },

  /** 加载发票抬头 */
  loadHeaders() {
    this.setData({ loading: true });
    api.invoice
      .getHeaders()
      .then((res) => {
        const data = res.data || res || {};
        this.setData({ headers: data.list || [], loading: false });
      })
      .catch(() => this.setData({ loading: false }));
  },

  /** 加载开票记录 */
  loadRecords(reset) {
    const page = reset ? 1 : this.data.page;
    this.setData({ loading: true });
    api.invoice
      .getRecords({ page, page_size: this.data.pageSize })
      .then((res) => {
        const data = res.data || res || {};
        const list = data.list || [];
        this.setData({
          records: reset ? list : this.data.records.concat(list),
          page: page + 1,
          hasMore: list.length >= this.data.pageSize,
          loading: false,
        });
      })
      .catch(() => this.setData({ loading: false, hasMore: false }));
  },

  /** 切换 tab */
  onTabChange(e) {
    const idx = e.currentTarget.dataset.idx;
    if (idx === this.data.activeTab) return;
    this.setData({ activeTab: idx, page: 1, hasMore: true });
    this.loadData(true);
  },

  /** 打开新增抬头表单 */
  onAddHeader() {
    this.setData({
      showForm: true,
      editing: null,
      form: { header_name: '', invoice_type: 1, tax_no: '', reg_address: '', reg_phone: '', bank_name: '', bank_account: '', is_default: 0 },
    });
  },

  /** 打开编辑抬头表单 */
  onEditHeader(e) {
    const id = e.currentTarget.dataset.id;
    const header = this.data.headers.find(function (h) {
      return String(h.id) === String(id);
    });
    if (!header) return;
    this.setData({
      showForm: true,
      editing: header,
      form: {
        header_name: header.header_name || '',
        invoice_type: header.invoice_type || 1,
        tax_no: header.tax_no || '',
        reg_address: header.reg_address || '',
        reg_phone: header.reg_phone || '',
        bank_name: header.bank_name || '',
        bank_account: header.bank_account || '',
        is_default: header.is_default || 0,
      },
    });
  },

  /** 表单字段输入 */
  onInput(e) {
    const field = e.currentTarget.dataset.field;
    const value = e.detail.value;
    const form = Object.assign({}, this.data.form);
    form[field] = value;
    this.setData({ form });
  },

  /** 切换发票类型 */
  onTypeChange(e) {
    const type = e.currentTarget.dataset.type;
    const form = Object.assign({}, this.data.form);
    form.invoice_type = Number(type);
    this.setData({ form });
  },

  /** 关闭表单 */
  onCloseForm() {
    this.setData({ showForm: false, editing: null });
  },

  /** 保存抬头 */
  onSaveHeader() {
    const form = this.data.form;
    if (!form.header_name) {
      wx.showToast({ title: '请填写抬头名称', icon: 'none' });
      return;
    }
    const payload = Object.assign({}, form);
    if (this.data.editing) {
      payload.id = this.data.editing.id;
    }

    api.invoice
      .saveHeader(payload)
      .then(() => {
        wx.showToast({ title: '保存成功', icon: 'success' });
        this.setData({ showForm: false, editing: null });
        this.loadHeaders();
      })
      .catch((e) => {
        wx.showToast({ title: (e && e.message) || '保存失败', icon: 'none' });
      });
  },

  /** 删除抬头 */
  onDeleteHeader(e) {
    const id = e.currentTarget.dataset.id;
    wx.showModal({
      title: '删除发票抬头',
      content: '确定删除该抬头吗？',
      success: (res) => {
        if (!res.confirm) return;
        api.invoice
          .deleteHeader(id)
          .then(() => {
            wx.showToast({ title: '已删除', icon: 'success' });
            this.loadHeaders();
          })
          .catch(() => wx.showToast({ title: '删除失败', icon: 'none' }));
      },
    });
  },

  /** 申请开票 */
  onApply(e) {
    const orderId = e.currentTarget.dataset.order;
    if (!orderId) {
      wx.showToast({ title: '请在订单详情中申请开票', icon: 'none' });
      return;
    }
    api.invoice
      .apply({ order_id: orderId, invoice_header_id: this.data.headers.length ? this.data.headers[0].id : 0 })
      .then(() => {
        wx.showToast({ title: '已提交开票申请', icon: 'success' });
        this.setData({ activeTab: 1, page: 1, hasMore: true });
        this.loadRecords(true);
      })
      .catch((e) => {
        wx.showToast({ title: (e && e.message) || '申请失败', icon: 'none' });
      });
  },
});
