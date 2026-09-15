const { get, post } = require('../utils/request');

module.exports = {
  getInfo: () => get('/api/v1/security/info'),
  deactivate: (data) => post('/api/v1/security/deactivate', data),
  changePassword: (data) => post('/api/v1/user/change-password', data),
};
