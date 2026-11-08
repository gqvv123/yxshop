/**
 * 地区数据
 * 对应 yxshop-php: /api/v1/region/*
 */
const { get } = require('../utils/request');

module.exports = {
  // 地区树
  getTree: (pid) => get('/api/v1/region/tree', pid ? { pid } : {}),

  // 省份列表
  getProvinces: () => get('/api/v1/region/provinces'),

  // 城市列表
  getCities: (pid) => get('/api/v1/region/cities', { pid }),

  // 区县列表
  getDistricts: (pid) => get('/api/v1/region/districts', { pid }),
};
