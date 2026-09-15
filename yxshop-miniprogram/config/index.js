/**
 * 环境配置
 *
 * development → 本地开发，指向 yxshop-php 本地端口（8777）
 * production   → 正式环境，上线前请将 apiBase 改为你的 HTTPS 域名
 *
 * 服务器域名白名单请在「微信公众平台 → 开发管理 → 开发设置」中配置：
 *   request合法域名:  https://your-domain.com
 *   uploadFile合法域名: https://your-domain.com
 *   downloadFile合法域名: https://your-domain.com
 */
const ENV = {
  development: {
    apiBase: 'http://127.0.0.1:8777',
    debug: true,
  },
  production: {
    // ★ 上线时把这里改成你的正式 HTTPS 域名
    apiBase: 'https://your-domain.com',
    debug: false,
  },
};

// 通过 accountInfoSyncSync 区分线上/体验/开发；找不到时按 development
let envKey = 'development';
try {
  const info = wx.getAccountInfoSync && wx.getAccountInfoSync();
  if (info && info.miniProgram && info.miniProgram.envVersion === 'release') {
    envKey = 'production';
  }
} catch (e) {
  // ignore
}

module.exports = {
  ...ENV[envKey],
  /** 业务 app_id（多租户场景），用户未指定时用此值兜底 */
  appId: 10001,
  /** 默认分页大小 */
  pageSize: 20,
  /** 当前 yxshop-php API 版本 */
  apiVersion: 'v1',
};
