/**
 * 微信支付统一封装
 *
 * 后端（PaymentService::createWechatPayment）返回：
 *   { payment_id, timeStamp, nonceStr, package, paySign, signType }
 * 兼容微信支付 V2（MD5/HMAC-SHA256）与 V3（RSA），signType 缺失时回退 MD5。
 *
 * 使用：
 *   const { callWxPay } = require('../../utils/pay');
 *   await callWxPay(res.pay_params);
 */

function callWxPay(params) {
  if (!params || !params.timeStamp || !params.nonceStr || !params.package || !params.paySign) {
    return Promise.reject(new Error('支付参数不完整'));
  }
  return new Promise((resolve, reject) => {
    wx.requestPayment({
      timeStamp: params.timeStamp,
      nonceStr: params.nonceStr,
      package: params.package,
      signType: params.signType || 'MD5',
      paySign: params.paySign,
      success: resolve,
      fail: reject,
    });
  });
}

module.exports = { callWxPay };
