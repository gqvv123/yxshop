/**
 * 商品相关
 *
 * 当前 yxshop-php 在 /api/v1 下未直接挂 item 路由（路由文件中无 item 组），
 * 但 ItemController 已实现以下方法，前端先以 RESTful 风格预埋；
 * 若后端路由名不同，统一在此文件调整，不必改页面。
 */
const { get, post } = require('../utils/request');

module.exports = {
  getList: (params) => get('/api/v1/item/list', params),
  getDetail: (id, params = {}) => get('/api/v1/item/detail', { id, ...params }),
  getHot: (params) => get('/api/v1/item/hot', params),
  getRecommended: (params) => get('/api/v1/item/recommended', params),
  getNew: (params) => get('/api/v1/item/new', params),
  search: (params) => get('/api/v1/item/search', params),
  /** 商品分类（如后端未提供，可暂时本地硬编码或对接其它路由） */
  getCategories: (params) => get('/api/v1/item/categories', params),

  // P0 新增
  getReviews: (params) => get('/api/v1/item/reviews', params),
  getRelated: (params) => get('/api/v1/item/related', params),
  view: (data) => post('/api/v1/item/view', data),

  // P1 新增：商品咨询/问答
  getConsultations: (params) => get('/api/v1/item/consultations', params),
  consult: (data) => post('/api/v1/item/consult', data),
};
