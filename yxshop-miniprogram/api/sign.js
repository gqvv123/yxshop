/**
 * 签到
 * 对应 yxshop-php: /api/v1/sign/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  // 签到状态
  getStatus: () => get('/api/v1/sign/status'),

  // 执行签到
  doSign: () => post('/api/v1/sign/do'),

  // 签到记录（近30天日历）
  getRecords: () => get('/api/v1/sign/records'),
};
