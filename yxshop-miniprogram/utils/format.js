/**
 * 通用格式化辅助
 */

/** 价格格式化：1234 -> "1,234.00" */
function fmtPrice(value) {
  const n = Number(value);
  if (!isFinite(n)) return '0.00';
  return n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

/** 时间戳/日期 -> "YYYY-MM-DD HH:mm" */
function fmtDate(input, withTime = true) {
  if (!input) return '';
  let d;
  if (typeof input === 'number') {
    // 兼容秒级时间戳
    d = new Date(input < 1e12 ? input * 1000 : input);
  } else {
    d = new Date(input);
  }
  if (isNaN(d.getTime())) return '';
  const pad = (n) => (n < 10 ? '0' + n : '' + n);
  const date = `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  if (!withTime) return date;
  return `${date} ${pad(d.getHours())}:${pad(d.getMinutes())}`;
}

/**
 * 订单状态映射
 *
 * 后端 Order 模型常量：
 *   status:           10=进行中, 20=已取消, 30=已完成
 *   pay_status:       10=未付款, 20=已付款
 *   delivery_status:  10=未发货, 20=已发货
 *   receipt_status:   10=未收货, 20=已收货
 *
 * 前端展示需要组合判断出更细粒度的状态：
 *   已取消 / 待付款 / 待发货 / 待收货 / 已完成
 */
function fmtOrderStatus(order) {
  // 兼容传整个 order 对象或只传 status 数字；null/undefined 安全处理
  if (order == null) return '未知状态';
  const s = typeof order === 'object' ? order : { status: order };
  const status      = Number(s.status ?? 0);
  const payStatus   = Number(s.pay_status ?? 0);
  const delivery    = Number(s.delivery_status ?? 0);
  const receipt     = Number(s.receipt_status ?? 0);

  // 20 = 已取消
  if (status === 20) return '已取消';

  // 30 = 已完成
  if (status === 30) return '已完成';

  // status=10 进行中，根据子状态细分
  if (status === 10) {
    if (payStatus !== 20) return '待付款';       // 未付款
    if (delivery !== 20) return '待发货';        // 已付款未发货
    if (receipt !== 20) return '待收货';         // 已发货未收货
    return '已完成';                              // 已收货但 status 还没更新为 30
  }

  return '未知状态';
}

/**
 * 获取订单流程状态码（用于列表筛选）
 * 返回值与前端 TAB 一致：10=待付款, 20=待发货, 30=待收货, 40=已完成, '-1'=已取消
 */
function getOrderFlowStatus(order) {
  if (!order) return 0;
  const status      = Number(order.status ?? 0);
  const payStatus   = Number(order.pay_status ?? 0);
  const delivery    = Number(order.delivery_status ?? 0);
  const receipt     = Number(order.receipt_status ?? 0);

  if (status === 20) return '-1';   // 已取消
  if (status === 30) return 40;     // 已完成
  if (status === 10) {
    if (payStatus !== 20) return 10;    // 待付款
    if (delivery !== 20) return 20;     // 待发货
    if (receipt !== 20) return 30;      // 待收货
    return 40;                          // 已完成
  }
  return 0;
}

/** 手机号脱敏 */
function maskPhone(phone) {
  if (!phone) return '';
  const s = String(phone);
  return s.length >= 11 ? s.slice(0, 3) + '****' + s.slice(7) : s;
}

module.exports = {
  fmtPrice,
  fmtDate,
  fmtOrderStatus,
  getOrderFlowStatus,
  maskPhone,
};
