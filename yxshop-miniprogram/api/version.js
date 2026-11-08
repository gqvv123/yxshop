/**
 * 应用版本
 * 对应 yxshop-php: /api/v1/version/*
 */
const { get } = require('../utils/request');

module.exports = {
  getLatest: (params) => get('/api/v1/version/latest', params),
  checkUpdate: (params) => get('/api/v1/version/check-update', params),
  getForceUpdate: (params) => get('/api/v1/version/force-update', params),
  getInfo: (params) => get('/api/v1/version/info', params),
};
