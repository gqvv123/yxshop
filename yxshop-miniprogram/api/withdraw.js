/**
 * 提现
 * 对应 yxshop-php: /api/v1/withdraw/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  // 提现申请
  apply: (data) => post('/api/v1/withdraw/apply', data),

  // 提现记录
  getRecords: (params) => get('/api/v1/withdraw/records', params),
};
