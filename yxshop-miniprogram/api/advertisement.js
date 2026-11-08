/**
 * 广告位
 * 对应 yxshop-php: /api/v1/advertisement/*
 */
const { get } = require('../utils/request');

module.exports = {
  // 有效广告列表：position 广告位（如 home_banner）
  getList: (position, limit) => get('/api/v1/advertisement/list', { position, limit }),
};
