/**
 * 促销活动
 * 对应 yxshop-php: /api/v1/promotion/*
 */
const { get } = require('../utils/request');

module.exports = {
  // 促销活动列表（进行中/未开始）
  getList: (params) => get('/api/v1/promotion/list', params),

  // 活动详情（含促销商品）
  getDetail: (id) => get('/api/v1/promotion/detail', { id }),

  // 促销商品列表
  getItems: (promId) => get('/api/v1/promotion/items', { prom_id: promId }),
};
