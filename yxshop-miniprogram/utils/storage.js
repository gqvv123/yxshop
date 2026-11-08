/**
 * 本地存储封装
 * 统一加 'yxshop:' 前缀，避免与其它小程序冲突
 */
const PREFIX = 'yxshop:';

const safe = (fn, fallback) => {
  try { return fn(); } catch (e) { return fallback; }
};

module.exports = {
  set(key, value) {
    return safe(() => wx.setStorageSync(PREFIX + key, value));
  },
  get(key, fallback = null) {
    return safe(() => {
      const v = wx.getStorageSync(PREFIX + key);
      return v === '' || v === undefined ? fallback : v;
    }, fallback);
  },
  remove(key) {
    return safe(() => wx.removeStorageSync(PREFIX + key));
  },
  clear() {
    // 仅清理本小程序自己的 key
    safe(() => {
      const info = wx.getStorageInfoSync();
      (info.keys || []).forEach((k) => {
        if (k.indexOf(PREFIX) === 0) wx.removeStorageSync(k);
      });
    });
  },
};
