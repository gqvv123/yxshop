/**
 * 系统配置
 * 对应 yxshop-php: /api/v1/config/*
 */
const { get } = require('../utils/request');

module.exports = {
  getSystemConfig: () => get('/api/v1/config/system'),
  getThemeConfig: () => get('/api/v1/config/theme'),
  getPaymentConfig: () => get('/api/v1/config/payment'),
  getSearchHotWords: () => get('/api/v1/config/search-hot-words'),
};
