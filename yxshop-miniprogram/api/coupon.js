/**
 * 优惠券
 * 对应 yxshop-php: /api/v1/coupon/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  // 基础优惠券列表
  getList: (params) => get('/api/v1/coupon/list', params),

  // P2 新增：可领优惠券列表
  getAvailable: () => get('/api/v1/coupon/available'),

  // P2 新增：领取优惠券
  claim: (couponId) => post('/api/v1/coupon/claim', { coupon_id: couponId }),

  // P2 新增：优惠券统计（未使用/已使用/已过期）
  getStats: () => get('/api/v1/coupon/stats'),

  // P2 增强：我的优惠券
  getMyList: (params) => get('/api/v1/coupon/my-list', params),
};
