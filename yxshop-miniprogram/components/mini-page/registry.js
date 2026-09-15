/**
 * 小程序装修组件注册表
 *
 * 开源版组件：始终可用
 * 商业版组件：仅在商业版目录存在时可用（通过文件存在性检测）
 */

// 开源版组件（始终注册）
const openSourceComponents = {
  search_bar: '/components/mini-page/search-bar/index',
  swiper: '/components/mini-page/swiper/index',
  notice_bar: '/components/mini-page/notice-bar/index',
  rich_text: '/components/mini-page/rich-text/index',
  image_ad: '/components/mini-page/image-ad/index',
  grid_nav: '/components/mini-page/grid-nav/index',
  product_list: '/components/mini-page/product-list/index',
  coupon: '/components/mini-page/coupon/index',
  coupon_receive: '/components/mini-page/coupon-receive/index',
  point_exchange: '/components/mini-page/point-exchange/index',
  promo_banner: '/components/mini-page/promo-banner/index',
  promo_grid: '/components/mini-page/promo-grid/index',
  brand_zone: '/components/mini-page/brand-zone/index',
};

// 商业版组件（仅商业版目录存在时注册）
const commercialComponents = {
  seckill: '/components/commercial/seckill/index',
  group_buy: '/components/commercial/group-buy/index',
};

// 检测商业版组件是否可用
const fs = wx.getFileSystemManager ? wx.getFileSystemManager() : null;
let commercialAvailable = false;
try {
  if (fs) {
    // 尝试访问商业版组件目录，存在则说明商业版已部署
    fs.accessSync('components/commercial/seckill/index.js');
    commercialAvailable = true;
  }
} catch (e) {
  commercialAvailable = false;
}

// 合并组件映射
const componentMap = { ...openSourceComponents };
if (commercialAvailable) {
  Object.assign(componentMap, commercialComponents);
}

const componentTypes = Object.keys(componentMap);

function getUsingComponents(types) {
  const result = {};
  (types || componentTypes).forEach((t) => {
    if (componentMap[t]) result['mp-' + t.replace(/_/g, '-')] = componentMap[t];
  });
  return result;
}

function getComponentTag(type) {
  return 'mp-' + type.replace(/_/g, '-');
}

module.exports = { componentMap, componentTypes, getUsingComponents, getComponentTag };
