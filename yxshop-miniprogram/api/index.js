/**
 * API 聚合入口
 * 业务页面只需 require('api') 即可访问全部接口
 *
 * 用法：
 *   const api = require('../../api/index');
 *   await api.cart.add({ item_id: 1, quantity: 2 });
 */

module.exports = {
  auth: require('./auth'),
  user: require('./user'),
  item: require('./item'),
  cart: require('./cart'),
  order: require('./order'),
  payment: require('./payment'),
  address: require('./address'),
  favorite: require('./favorite'),
  notification: require('./notification'),
  upload: require('./upload'),
  config: require('./config'),
  version: require('./version'),
  miniPage: require('./mini-page'),
  coupon: require('./coupon'),
  pointItem: require('./point-item'),
  category: require('./category'),
  brand: require('./brand'),
  balance: require('./balance'),
  withdraw: require('./withdraw'),
  feedback: require('./feedback'),
  contract: require('./contract'),
  security: require('./security'),
  verify: require('./verify'),
  sign: require('./sign'),
  point: require('./point'),
  task: require('./task'),
}
