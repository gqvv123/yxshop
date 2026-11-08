const { get, post } = require('../utils/request');

module.exports = {
  getList: (params) => get('/api/v1/point-item/list', params),
  exchange: (data) => post('/api/v1/point-item/exchange', data),
};