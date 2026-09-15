/**
 * 支付
 * 对应 yxshop-php: /api/v1/payment/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  create: (data) => post('/api/v1/payment/create', data),
  getStatus: (params) => get('/api/v1/payment/status', params),
  refund: (data) => post('/api/v1/payment/refund', data),
};
