/**
 * 帮助中心 / 商城公告
 * 对应 yxshop-php: /api/v1/article/*
 */
const { get } = require('../utils/request');

module.exports = {
  // 文章列表（仅已发布）
  getList: (params) => get('/api/v1/article/list', params),

  // 文章详情
  getDetail: (id) => get('/api/v1/article/detail', { id }),

  // 文章分类（帮助中心导航）
  getCategories: () => get('/api/v1/article/categories'),
};
