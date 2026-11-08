/**
 * 认证相关 API
 * 对应 yxshop-php: /api/v1/auth/*
 */
const { post } = require('../utils/request');

module.exports = {
  /** 用户登录 */
  login: (data) => post('/api/v1/auth/login', data, { skipAuth: true }),

  /** 用户注册 */
  register: (data) => post('/api/v1/auth/register', data, { skipAuth: true }),

  /** 退出登录 */
  logout: () => post('/api/v1/auth/logout'),

  /** 刷新 token */
  refreshToken: (refresh_token) =>
    post('/api/v1/auth/refresh-token', { refresh_token }, { skipAuth: true }),

  /** 忘记密码 */
  forgotPassword: (data) => post('/api/v1/auth/forgot-password', data, { skipAuth: true }),

  /** 重置密码 */
  resetPassword: (data) => post('/api/v1/auth/reset-password', data, { skipAuth: true }),

  /**
   * 微信小程序一键登录
   * @param {object} payload { code, app_id?, profile? }
   */
  wxLogin: (payload) => post('/api/v1/auth/wx-login', payload, { skipAuth: true }),

  /**
   * 同步微信资料（昵称/头像）
   * @param {object} profile { nickname?, avatar_url?, gender? }
   */
  updateWxProfile: (profile) => post('/api/v1/auth/wx-profile', { profile }),

  /**
   * 微信手机号一键绑定
   * @param {object} payload { code } 或 { encrypted_data, iv }
   */
  bindPhone: (payload) => post('/api/v1/auth/bind-phone', payload),
};

