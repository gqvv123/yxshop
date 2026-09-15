/**
 * 订单
 * 对应 yxshop-php: /api/v1/order/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  create: (data) => post('/api/v1/order/create', data),
  getList: (params) => get('/api/v1/order/list', params),
  statusCounts: () => get('/api/v1/order/status-counts'),
  getDetail: (id) => get('/api/v1/order/detail', { id }),
  cancel: (id, reason = '') => post('/api/v1/order/cancel', { id, reason }),
  confirm: (id) => post('/api/v1/order/confirm', { id }),
  refund: (data) => post('/api/v1/order/refund', data),
  refundDetail: (params) => get('/api/v1/order/refund-detail', params),
  review: (data) => post('/api/v1/order/review', data),

  // P1 新增：再次购买
  reorder: (id) => post('/api/v1/order/reorder', { id }),

  // P1 新增：订单物流
  getLogistics: (id) => get('/api/v1/order/logistics', { id }),

  // P1 新增：提醒发货
  remindShip: (id) => post('/api/v1/order/remind-ship', { id }),

  // P1 新增：删除订单
  delete: (id) => post('/api/v1/order/delete', { id }),
};
