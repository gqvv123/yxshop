/**
 * 微信登录辅助
 *
 * 流程：
 *   1) wx.login() 拿 code
 *   2) 可选：wx.getUserProfile / wx.chooseAvatar 拿到昵称头像
 *   3) 调后端 /api/v1/auth/wx-login，后端 code2session 拿 openid 并签发 JWT
 *   4) auth.saveAuth() 写入本地，触发自动加 Bearer
 *
 * 推荐人绑定：
 *   登录时自动读取 app.globalData.referrerId 或本地缓存 referrerId，
 *   传入 ref 参数，后端在创建用户时绑定 referrer_id。
 */
const api = require('../api/index');
const auth = require('./auth');
const config = require('../config/index');

/** 调用 wx.login，返回 code */
function wxLogin() {
  return new Promise((resolve, reject) => {
    wx.login({
      success: (res) => {
        if (res.code) resolve(res.code);
        else reject(new Error('wx.login 未返回 code'));
      },
      fail: reject,
    });
  });
}

/**
 * 获取推荐人ID（优先 globalData，回退本地缓存）
 * @returns {number}
 */
function getReferrerId() {
  const app = getApp();
  if (app && app.globalData && app.globalData.referrerId) {
    return app.globalData.referrerId;
  }
  try {
    return wx.getStorageSync('referrerId') || 0;
  } catch (e) {
    return 0;
  }
}

/**
 * 一键登录
 * @param {object} options
 *   - profile?: { nickname, avatar_url, gender }
 *   - app_id?: number
 *   - ref?: number  推荐人ID（不传则自动读取 globalData/缓存）
 * @returns 后端 wxLogin 返回的 data 部分
 */
async function loginWithWx(options = {}) {
  const code = await wxLogin();
  const ref = options.ref || getReferrerId();
  const data = await api.auth.wxLogin({
    code,
    app_id: options.app_id || config.appId,
    profile: options.profile || undefined,
    ref: ref || undefined,
  });
  auth.saveAuth(data);
  if (getApp()) getApp().globalData.userInfo = data.user;
  // 登录成功后清除缓存的 referrerId（已绑定）
  if (ref) {
    try { wx.removeStorageSync('referrerId'); } catch (e) {}
  }
  return data;
}

module.exports = { wxLogin, loginWithWx, getReferrerId };
