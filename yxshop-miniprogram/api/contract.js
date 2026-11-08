const { get, post } = require('../utils/request');

module.exports = {
  myList: (params) => get('/api/v1/contract/my-list', params),
  detail: (params) => get('/api/v1/contract/detail', params),
  stats: () => get('/api/v1/contract/stats'),
};
