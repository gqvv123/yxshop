const api = require('../../../api/index');
const imageUtil = require('../../../utils/image');

Page({
  data: {
    statusBarHeight: 20,
    /** 认证状态: 0=未申请 1=审核中 2=已认证 3=已拒绝 */
    status: 0,
    statusText: '未认证',
    /** 认证详情（已提交过的） */
    detail: null,
    /** 表单 */
    form: {
      company_name: '',
      credit_code: '',
      legal_person: '',
      contact_name: '',
      contact_phone: '',
      license_image: '',
      id_card_front: '',
      id_card_back: '',
    },
    /** 已上传图片预览 */
    licensePreview: '',
    idFrontPreview: '',
    idBackPreview: '',
    /** 提交中 */
    submitting: false,
    /** 认证权益列表 */
    benefits: [
      { icon: 'tag', title: '企业专属价', desc: '享受企业采购价折扣' },
      { icon: 'clock', title: '月结账期', desc: '最长30天账期，先货后款' },
      { icon: 'headphones', title: '专属客服', desc: '7×24小时企业服务专线' },
      { icon: 'truck', title: '优先发货', desc: '订单优先处理，快速送达' },
    ],
  },

  onLoad() {
    try {
      const sysInfo = wx.getWindowInfo();
      this.setData({ statusBarHeight: sysInfo.statusBarHeight || 20 });
    } catch (e) {}
    this.loadInfo();
  },

  onShow() {
    // 每次显示时刷新
    if (this.data.status !== 0) {
      this.loadInfo();
    }
  },

  async loadInfo() {
    try {
      const res = await api.verify.getInfo();
      if (res) {
        const detail = res.detail || null;
        this.setData({
          status: res.status || 0,
          statusText: res.status_text || '未认证',
          detail: detail,
          'form.company_name': (detail && detail.company_name) || '',
          'form.credit_code': (detail && detail.credit_code) || '',
          'form.legal_person': (detail && detail.legal_person) || '',
          'form.contact_name': (detail && detail.contact_name) || '',
          'form.contact_phone': (detail && detail.contact_phone) || '',
          'form.license_image': (detail && detail.license_image) || '',
          'form.id_card_front': (detail && detail.id_card_front) || '',
          'form.id_card_back': (detail && detail.id_card_back) || '',
          licensePreview: (detail && detail.license_image) ? imageUtil.resolve(detail.license_image) : '',
          idFrontPreview: (detail && detail.id_card_front) ? imageUtil.resolve(detail.id_card_front) : '',
          idBackPreview: (detail && detail.id_card_back) ? imageUtil.resolve(detail.id_card_back) : '',
        });
      }
    } catch (e) {
      // ignore
    }
  },

  onBack() {
    const pages = getCurrentPages();
    if (pages.length > 1) {
      wx.navigateBack();
    } else {
      wx.switchTab({ url: '/pages/profile/profile' });
    }
  },

  noop() {},

  // ===== 表单输入 =====
  onInput(e) {
    const field = e.currentTarget.dataset.field;
    this.setData({ ['form.' + field]: e.detail.value });
  },

  // ===== 图片上传 =====
  async onUploadLicense() {
    if (this.data.status === 1) return;
    try {
      const res = await wx.chooseMedia({
        count: 1,
        mediaType: ['image'],
        sourceType: ['album', 'camera'],
        sizeType: ['compressed'],
      });
      if (!res.tempFiles || !res.tempFiles.length) return;
      const tempPath = res.tempFiles[0].tempFilePath;
      this.setData({ licensePreview: tempPath });

      try {
        const uploadRes = await api.upload.uploadImage(tempPath, { scene: 'license' });
        const url = (uploadRes && (uploadRes.url || uploadRes.path)) || '';
        this.setData({ 'form.license_image': url });
      } catch (err) {
        wx.showToast({ title: '上传失败，请重试', icon: 'none' });
      }
    } catch (e) {
      // 用户取消
    }
  },

  async onUploadIdCard(e) {
    if (this.data.status === 1) return;
    const field = e.currentTarget.dataset.field;
    const previewField = field === 'id_card_front' ? 'idFrontPreview' : 'idBackPreview';
    try {
      const res = await wx.chooseMedia({
        count: 1,
        mediaType: ['image'],
        sourceType: ['album', 'camera'],
        sizeType: ['compressed'],
      });
      if (!res.tempFiles || !res.tempFiles.length) return;
      const tempPath = res.tempFiles[0].tempFilePath;
      this.setData({ [previewField]: tempPath });

      try {
        const uploadRes = await api.upload.uploadImage(tempPath, { scene: 'idcard' });
        const url = (uploadRes && (uploadRes.url || uploadRes.path)) || '';
        this.setData({ ['form.' + field]: url });
      } catch (err) {
        wx.showToast({ title: '上传失败，请重试', icon: 'none' });
      }
    } catch (e) {
      // 用户取消
    }
  },

  // ===== 提交 =====
  async onSubmit() {
    if (this.data.submitting) return;
    const f = this.data.form;

    if (!f.company_name) {
      return wx.showToast({ title: '请填写企业名称', icon: 'none' });
    }
    if (!f.credit_code) {
      return wx.showToast({ title: '请填写统一社会信用代码', icon: 'none' });
    }
    if (!f.legal_person) {
      return wx.showToast({ title: '请填写法定代表人', icon: 'none' });
    }
    if (!f.contact_phone) {
      return wx.showToast({ title: '请填写联系电话', icon: 'none' });
    }
    if (!f.license_image) {
      return wx.showToast({ title: '请上传营业执照', icon: 'none' });
    }

    this.setData({ submitting: true });
    try {
      await api.verify.submit(f);
      wx.showToast({ title: '提交成功', icon: 'success' });
      setTimeout(() => {
        this.loadInfo();
      }, 1500);
    } catch (e) {
      // toast 已弹
    } finally {
      this.setData({ submitting: false });
    }
  },
});
