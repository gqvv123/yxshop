/**
 * 品牌相关
 * 对应 yxshop-php: /api/v1/brand/*
 */
const { get } = require('../utils/request');

module.exports = {
  /** 获取品牌列表（默认读取系统启用的品牌） */
  getList: (params) => get('/api/v1/brand/list', params),
};
