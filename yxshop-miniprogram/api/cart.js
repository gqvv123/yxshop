/**
 * 购物车
 * 对应 yxshop-php: /api/v1/cart/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  getList: (params) => get('/api/v1/cart/list', params),
  getCount: (params) => get('/api/v1/cart/count', params),
  checkoutPreview: (params) => get('/api/v1/cart/checkout-preview', params),
  add: (data) => post('/api/v1/cart/add', data),
  update: (data) => post('/api/v1/cart/update', data),
  remove: (data) => post('/api/v1/cart/remove', data),
  clear: (data) => post('/api/v1/cart/clear', data),
  select: (data) => post('/api/v1/cart/select', data),
  selectAll: (data) => post('/api/v1/cart/select-all', data),
};
