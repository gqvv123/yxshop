/**
 * 通用请求层
 *
 * 与 yxshop-php 后端响应契约对齐：
 *   { code: 0|非0, message: string, data: any }
 *   code === 0 视为业务成功，其它一律抛错（含 message）。
 *   HTTP 401 触发清登录态并跳登录页。
 *
 * 使用：
 *   const { request, get, post } = require('utils/request');
 *   const data = await get('/api/v1/item/list', { page: 1 });
 */
const config = require('../config/index');
const auth = require('./auth');

/** 同时进行的请求计数 -> 控制 loading */
let loadingCount = 0;
const showLoading = () => {
  if (loadingCount === 0) {
    wx.showNavigationBarLoading();
  }
  loadingCount += 1;
};
const hideLoading = () => {
  loadingCount = Math.max(0, loadingCount - 1);
  if (loadingCount === 0) {
    wx.hideNavigationBarLoading();
  }
};

const buildUrl = (path) => {
  if (/^https?:\/\//.test(path)) return path;
  const base = config.apiBase.replace(/\/+$/, '');
  return base + (path.startsWith('/') ? path : '/' + path);
};

/** 防止并发 401 时重复跳转登录页 */
let isHandlingAuth = false;

/** token 静默刷新状态 */
let isRefreshing = false;
let refreshQueue = [];

/**
 * 静默刷新 token：用 refresh_token 换新 token
 * 成功后重放队列中等待的请求，失败则走登录流程
 */
async function tryRefreshToken() {
  if (isRefreshing) {
    // 已有刷新进行中，返回一个 promise 加入队列等待
    return new Promise((resolve, reject) => {
      refreshQueue.push({ resolve, reject });
    });
  }

  const refreshToken = auth.getRefreshToken();
  if (!refreshToken) {
    return Promise.reject(new Error('no refresh token'));
  }

  isRefreshing = true;
  try {
    const { post } = module.exports;
    const data = await post('/api/v1/auth/refresh-token', { refresh_token: refreshToken }, { skipAuth: true, silent: true, showError: false });
    auth.saveAuth(data);
    // 重放队列
    refreshQueue.forEach(({ resolve }) => resolve(data));
    refreshQueue = [];
    return data;
  } catch (e) {
    // 刷新失败，清空队列并拒绝
    refreshQueue.forEach(({ reject }) => reject(e));
    refreshQueue = [];
    throw e;
  } finally {
    isRefreshing = false;
  }
}

/**
 * 统一处理未登录/登录过期：清 token、toast、跳登录页。
 * 后端有两种表达：HTTP 401，或 HTTP 200 + {code:401,msg:'未提供认证令牌'}。
 */
function handleAuthRequired(showError = true) {
  if (isHandlingAuth) return;
  isHandlingAuth = true;

  auth.clearAuth();
  if (showError) {
    wx.showToast({ title: '请先登录', icon: 'none' });
  }
  const pages = getCurrentPages();
  const currentPath = pages.length ? pages[pages.length - 1].route : '';
  if (!/login\/login$/.test(currentPath)) {
    wx.navigateTo({
      url: '/pages/login/login',
      complete: () => { isHandlingAuth = false; },
    });
  } else {
    isHandlingAuth = false;
  }
}

/**
 * 核心请求
 * @param {string} url 路径或完整 URL
 * @param {object} options
 *   - method: 'GET'|'POST'|'PUT'|'DELETE' 默认 GET
 *   - data: 业务参数
 *   - header: 额外请求头
 *   - silent: 不显示导航栏 loading（如 token 静默刷新）
 *   - skipAuth: 不附带 Bearer token（如登录、注册）
 *   - showError: 失败时是否 toast 默认 true
 */
function request(url, options = {}) {
  const {
    method = 'GET',
    data = {},
    header = {},
    silent = false,
    skipAuth = false,
    showError = true,
  } = options;

  if (!silent) showLoading();

  const finalHeader = {
    'Content-Type': 'application/json',
    Accept: 'application/json',
    'X-Version': config.apiVersion,
    ...header,
  };

  if (!skipAuth) {
    const token = auth.getToken();
    if (token) finalHeader.Authorization = 'Bearer ' + token;
  }

  return new Promise((resolve, reject) => {
    wx.request({
      url: buildUrl(url),
      method,
      data,
      header: finalHeader,
      success(res) {
        const status = res.statusCode;

        // 401: 登录失效
        if (status === 401) {
          handleAuthRequired(showError);
          const err = new Error('请先登录');
          err.code = 401;
          err.authRequired = true;
          return reject(err);
        }

        // 其它 HTTP 错误
        if (status < 200 || status >= 300) {
          const msg = (res.data && res.data.message) || `网络错误 (${status})`;
          if (showError) wx.showToast({ title: msg, icon: 'none' });
          return reject(new Error(msg));
        }

        // 业务层 code
        const body = res.data || {};
        // 兼容两种成功表达：code === 0 / success === true
        const isSuccess = body.code === 0 || body.code === '0' || body.success === true;
        if (isSuccess) {
          // data 为 null 时返回空对象，防止下游代码在 null 上访问属性
          const data = body.data === undefined ? body : body.data;
          return resolve(data === null ? {} : data);
        }

        const msg = body.message || body.msg || '请求失败';
        const code = Number(body.code);
        const isAuthError = code === 401
          || msg === '未提供认证令牌'
          || msg === '认证令牌无效'
          || msg === '登录已过期'
          || msg === '用户未登录';

        if (isAuthError) {
          handleAuthRequired(showError);
          const err = new Error('请先登录');
          err.code = 401;
          err.authRequired = true;
          err.body = body;
          return reject(err);
        }

        if (showError) wx.showToast({ title: msg, icon: 'none' });
        const err = new Error(msg);
        err.code = body.code;
        err.body = body;
        return reject(err);
      },
      fail(err) {
        const msg = err && err.errMsg ? err.errMsg : '网络异常';
        if (showError) wx.showToast({ title: '网络异常，请稍后再试', icon: 'none' });
        reject(new Error(msg));
      },
      complete() {
        if (!silent) hideLoading();
      },
    });
  });
}

const get = (url, params, options = {}) => request(url, { ...options, method: 'GET', data: params });
const post = (url, data, options = {}) => request(url, { ...options, method: 'POST', data });
const put = (url, data, options = {}) => request(url, { ...options, method: 'PUT', data });
const del = (url, data, options = {}) => request(url, { ...options, method: 'DELETE', data });

/**
 * 文件上传（保留 token & 业务包装）
 */
function upload(url, filePath, name = 'file', formData = {}) {
  showLoading();
  const token = auth.getToken();
  return new Promise((resolve, reject) => {
    wx.uploadFile({
      url: buildUrl(url),
      filePath,
      name,
      formData,
      header: token ? { Authorization: 'Bearer ' + token } : {},
      success(res) {
        try {
          const body = JSON.parse(res.data);
          const isSuccess = body.code === 0 || body.success === true;
          if (isSuccess) {
            const data = body.data === undefined ? body : body.data;
            return resolve(data === null ? {} : data);
          }
          wx.showToast({ title: body.message || '上传失败', icon: 'none' });
          reject(new Error(body.message || '上传失败'));
        } catch (e) {
          reject(new Error('上传响应解析失败'));
        }
      },
      fail(err) {
        wx.showToast({ title: '上传失败', icon: 'none' });
        reject(err);
      },
      complete() { hideLoading(); },
    });
  });
}

module.exports = { request, get, post, put, del, upload };
