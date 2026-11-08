/**
 * 企业认证
 * 对应 yxshop-php: /api/v1/verify/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  getInfo: () => get('/api/v1/verify/info'),
  submit: (data) => post('/api/v1/verify/submit', data),
};
