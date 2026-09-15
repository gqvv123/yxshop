/**
 * 图片 URL 工具
 *
 * 后端返回的图片路径可能是：
 *   1. 完整 URL（https://...）→ 直接使用
 *   2. 绝对路径（/uploads/...）→ 拼接 apiBase
 *   3. 相对路径（uploads/...）→ 拼接 apiBase + /
 *   4. 空值 → 返回空字符串
 */
const config = require('../config/index');

const apiBase = (config.apiBase || '').replace(/\/+$/, '');

function resolve(url) {
  if (!url) return '';
  if (typeof url !== 'string') return '';
  var trimmed = url.trim();
  if (!trimmed || trimmed === '0') return '';
  // 完整 URL
  if (/^https?:\/\//i.test(trimmed)) return trimmed;
  // 协议相对 URL
  if (/^\/\//.test(trimmed)) return 'https:' + trimmed;
  // 绝对路径
  if (/^\//.test(trimmed)) return apiBase + trimmed;
  // 相对路径
  return apiBase + '/' + trimmed;
}

/**
 * 从商品对象中提取主图 URL
 * 兼容多种数据结构：
 *   - item.images[0].url（后端关联模型）
 *   - item.cover（自定义字段）
 *   - item.image（自定义字段）
 *   - item.main_image（后端 accessor，可能是 ID 或 URL）
 *   - item.goods_image（兼容字段）
 */
function getItemCover(item) {
  if (!item) return '';
  // 优先从 images 关联数组取
  if (Array.isArray(item.images) && item.images.length) {
    var main = item.images.find(function (img) { return img.is_main; });
    var img = main || item.images[0];
    if (img && img.url) return resolve(img.url);
    if (img && img.image_id && typeof img.image_id === 'string') return resolve(img.image_id);
  }
  // 降级到字段
  if (item.cover) return resolve(item.cover);
  if (item.image) return resolve(item.image);
  if (item.main_image && typeof item.main_image === 'string') return resolve(item.main_image);
  if (item.goods_image) return resolve(item.goods_image);
  return '';
}

module.exports = {
  resolve: resolve,
  getItemCover: getItemCover,
};
