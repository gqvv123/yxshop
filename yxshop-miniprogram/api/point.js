/**
 * 积分商城
 * 对应 yxshop-php: /api/v1/point/* 和 /api/v1/point-item/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  // 积分商城首页（聚合）
  getCenter: () => get('/api/v1/point/center'),

  // 积分商品列表
  getList: (params) => get('/api/v1/point-item/list', params),

  // 积分兑换
  exchange: (data) => post('/api/v1/point-item/exchange', data),

  // 兑换记录
  getRecords: (params) => get('/api/v1/point-item/records', params),

  // 积分流水
  getIntegralLog: (params) => get('/api/v1/user/integral-log', params),

  // 积分统计
  getIntegralStats: () => get('/api/v1/user/integral-stats'),
};
