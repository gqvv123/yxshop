/**
 * 物流配送
 * 对应 yxshop-php: /api/v1/delivery/*
 */
const { get } = require('../utils/request');

module.exports = {
  // 快递公司列表（下单时可选）
  getExpresses: () => get('/api/v1/delivery/expresses'),

  // 配送方式列表
  getList: (params) => get('/api/v1/delivery/list', params),

  // 配送方式详情（含计费规则）
  getDetail: (id) => get('/api/v1/delivery/detail', { id }),

  // 运费试算：delivery_id 配送方式、quantity 件数、amount 金额、region_id 收货地区
  getFreight: (params) => get('/api/v1/delivery/freight', params),
};
