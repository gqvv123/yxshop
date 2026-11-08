/**
 * 收藏
 * 对应 yxshop-php: /api/v1/favorite/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  getList: (params) => get('/api/v1/favorite/list', params),
  add: (data) => post('/api/v1/favorite/add', data),
  remove: (data) => post('/api/v1/favorite/remove', data),
  check: (params) => get('/api/v1/favorite/check', params),
};
