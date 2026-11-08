/**
 * 登录态管理
 *
 * yxshop-php 的认证模型：
 *   - login 接口返回 { token, refresh_token, expires_in, user }
 *   - 后续 API 通过 Authorization: Bearer <token> 鉴权
 */
const storage = require('./storage');

const TOKEN_KEY = 'token';
const REFRESH_KEY = 'refreshToken';
const USER_KEY = 'user';
const EXP_KEY = 'tokenExpireAt';

module.exports = {
  saveAuth({ token, refresh_token, expires_in, user }) {
    storage.set(TOKEN_KEY, token);
    if (refresh_token) storage.set(REFRESH_KEY, refresh_token);
    if (expires_in) storage.set(EXP_KEY, Date.now() + Number(expires_in) * 1000);
    if (user) storage.set(USER_KEY, user);
  },

  getToken() {
    return storage.get(TOKEN_KEY);
  },

  getRefreshToken() {
    return storage.get(REFRESH_KEY);
  },

  getUser() {
    return storage.get(USER_KEY);
  },

  setUser(user) {
    storage.set(USER_KEY, user);
  },

  isExpired() {
    const exp = storage.get(EXP_KEY, 0);
    if (!exp) return false; // 未存过期时间则按未过期处理
    return Date.now() >= exp;
  },

  isLoggedIn() {
    return !!storage.get(TOKEN_KEY);
  },

  clearAuth() {
    storage.remove(TOKEN_KEY);
    storage.remove(REFRESH_KEY);
    storage.remove(USER_KEY);
    storage.remove(EXP_KEY);
  },
};
