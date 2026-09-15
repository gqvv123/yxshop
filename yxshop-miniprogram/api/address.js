/**
 * 收货地址
 * 对应 yxshop-php: /api/v1/address/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  getList: (params) => get('/api/v1/address/list', params),
  add: (data) => post('/api/v1/address/add', data),
  update: (data) => post('/api/v1/address/update', data),
  delete: (id) => post('/api/v1/address/delete', { id }),
  setDefault: (id) => post('/api/v1/address/set-default', { id }),
};
