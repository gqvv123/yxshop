/**
 * 用户信息相关
 * 对应 yxshop-php: /api/v1/user/*
 */
const { get, post } = require('../utils/request');

module.exports = {
  getInfo: () => get('/api/v1/user/info'),
  updateInfo: (data) => post('/api/v1/user/update-info', data),
  updateAvatar: (data) => post('/api/v1/user/update-avatar', data),
  changePassword: (data) => post('/api/v1/user/change-password', data),

  // P1 新增：用户综合信息（个人中心聚合）
  getProfile: () => get('/api/v1/user/profile'),

  // P1 新增：浏览足迹
  getFootprint: (params) => get('/api/v1/user/footprint', params),
  removeFootprint: (viewId) => post('/api/v1/user/footprint-remove', { view_id: viewId }),
  clearFootprint: () => post('/api/v1/user/footprint-clear'),

  // P3 新增：搜索历史
  getSearchHistory: (params) => get('/api/v1/user/search-history', params),
  clearSearchHistory: () => post('/api/v1/user/search-history-clear'),
  removeSearchHistory: (id) => post('/api/v1/user/search-history-remove', { id }),

  // P3 新增：用户等级详情
  getLevel: () => get('/api/v1/user/level'),
};
