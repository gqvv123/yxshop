const { get } = require('../utils/request');

module.exports = {
  getHomePage: (params) => get('/api/v1/mini-page/home', params),
};