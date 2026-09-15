/**
 * 站内通知
 * 对应 yxshop-php: /api/v1/notification/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  getList: (params) => get('/api/v1/notification/list', params),
  markRead: (id) => post('/api/v1/notification/mark-read', { id }),
  unreadCount: () => get('/api/v1/notification/unread-count'),
};
