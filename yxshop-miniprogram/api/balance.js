/**
 * 余额管理
 * 对应 yxshop-php: /api/v1/user/balance, /api/v1/recharge/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  // 余额信息
  getBalance: () => get('/api/v1/user/balance'),

  // 余额流水
  getMoneyLog: (params) => get('/api/v1/user/money-log', params),

  // 收支统计
  getMoneyStats: () => get('/api/v1/user/money-stats'),

  // 充值套餐
  getPackages: () => get('/api/v1/recharge/packages'),

  // 创建充值订单
  createRecharge: (packageId) => post('/api/v1/recharge/create', { package_id: packageId }),

  // 充值记录
  getRechargeRecords: (params) => get('/api/v1/recharge/records', params),
};
