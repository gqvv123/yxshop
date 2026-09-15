/**
 * 应用版本
 * 对应 yxshop-php: /api/v1/version/*
 */
const { get } = require('../utils/request');

module.exports = {
  getLatest: () => get('/api/v1/version/latest'),
  checkUpdate: (params) => get('/api/v1/version/check-update', params),
  getForceUpdate: () => get('/api/v1/version/force-update'),
  getInfo: () => get('/api/v1/version/info'),
};
