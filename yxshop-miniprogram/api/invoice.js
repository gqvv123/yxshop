/**
 * 发票
 * 对应 yxshop-php: /api/v1/invoice/*
 */
const { get, post, del } = require('../utils/request');

module.exports = {
  // 我的发票抬头列表
  getHeaders: () => get('/api/v1/invoice/headers'),

  // 保存发票抬头（新增 / 编辑）
  saveHeader: (data) => post('/api/v1/invoice/header', data),

  // 删除发票抬头
  deleteHeader: (id) => del(`/api/v1/invoice/header/${id}`),

  // 申请开票
  apply: (data) => post('/api/v1/invoice/apply', data),

  // 我的开票记录
  getRecords: (params) => get('/api/v1/invoice/records', params),
};
