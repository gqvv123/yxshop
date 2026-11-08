/**
 * 分类
 * 对应 yxshop-php: /api/v1/category/*
 */
const { get } = require('../utils/request');

module.exports = {
  tree: (params) => get('/api/v1/category/tree', params),
  items: (params) => get('/api/v1/category/items', params),
  brands: (params) => get('/api/v1/category/brands', params),
};
