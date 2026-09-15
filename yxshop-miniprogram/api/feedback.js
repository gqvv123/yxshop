const { get, post } = require('../utils/request');

module.exports = {
  submit: (data) => post('/api/v1/feedback/submit', data),
  myList: (params) => get('/api/v1/feedback/my-list', params),
};
