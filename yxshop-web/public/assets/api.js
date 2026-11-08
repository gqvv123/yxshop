/**
 * 盈锐商贸 · 统一 API 层
 * 默认同源 /api/v1（由 server.js 代理到 Webman 后端），不依赖任何第三方库。
 * 如需直连其它后端：在地址栏加 ?api=http://host:port 或
 * localStorage.setItem('yxshop_api_base', 'http://host:port')
 */
(function (global) {
  'use strict';

  var TOKEN_KEY = 'yxshop_h5_token';
  var USER_KEY = 'yxshop_h5_user';

  function apiBase() {
    var fromQuery = new URLSearchParams(location.search).get('api');
    var fromStore = localStorage.getItem('yxshop_api_base');
    var base = (fromQuery || fromStore || '').replace(/\/$/, '');
    return base; // '' = 同源代理
  }

  function endpoint(path) {
    if (/^https?:\/\//i.test(path)) return path;
    var p = path.charAt(0) === '/' ? path : '/' + path;
    return apiBase() + '/api/v1' + p;
  }

  function getToken() { return localStorage.getItem(TOKEN_KEY) || ''; }
  function setToken(t) { t ? localStorage.setItem(TOKEN_KEY, t) : localStorage.removeItem(TOKEN_KEY); }
  function getStoredUser() {
    try { return JSON.parse(localStorage.getItem(USER_KEY) || 'null'); } catch (e) { return null; }
  }
  function setStoredUser(u) { u ? localStorage.setItem(USER_KEY, JSON.stringify(u)) : localStorage.removeItem(USER_KEY); }
  function logout() { setToken(''); setStoredUser(null); }

  function request(path, opts) {
    opts = opts || {};
    var method = (opts.method || 'GET').toUpperCase();
    var headers = { 'Content-Type': 'application/json' };
    var token = getToken();
    if (token) headers.Authorization = 'Bearer ' + token;

    var url = endpoint(path);
    if (opts.params) {
      var qs = new URLSearchParams();
      Object.keys(opts.params).forEach(function (k) {
        if (opts.params[k] !== undefined && opts.params[k] !== null && opts.params[k] !== '') qs.set(k, opts.params[k]);
      });
      var s = qs.toString();
      if (s) url += (url.indexOf('?') >= 0 ? '&' : '?') + s;
    }

    var init = { method: method, headers: headers };
    if (opts.body !== undefined && method !== 'GET') init.body = JSON.stringify(opts.body);

    return fetch(url, init).then(function (res) {
      return res.text().then(function (text) {
        var json;
        try { json = JSON.parse(text); } catch (e) {
          throw new Error('后端返回异常（HTTP ' + res.status + '）');
        }
        if (!res.ok && json.code === undefined) throw new Error(json.message || ('HTTP ' + res.status));
        if (json.code === 401) {
          logout();
          if (global.YXShop && global.YXShop.onUnauthorized) global.YXShop.onUnauthorized();
        }
        if (json.code !== 0 && json.code !== '0') {
          var err = new Error(json.message || '请求失败');
          err.code = json.code;
          err.payload = json.data;
          throw err;
        }
        return json.data;
      });
    }).catch(function (e) {
      if (e instanceof TypeError) throw new Error('无法连接后端服务，请确认 Webman 已启动（默认 8777 端口）');
      throw e;
    });
  }

  /** 从各种返回结构里取出数组，兼容 {list,data}/{data,total}/{items}/纯数组 */
  function list(data) {
    if (!data) return [];
    if (Array.isArray(data)) return data;
    if (Array.isArray(data.list)) return data.list;
    if (Array.isArray(data.data)) return data.data;
    if (Array.isArray(data.items)) return data.items;
    if (Array.isArray(data.records)) return data.records;
    return [];
  }
  function total(data, fallback) {
    if (!data || typeof data !== 'object') return fallback || 0;
    if (typeof data.total === 'number') return data.total;
    if (typeof data.count === 'number') return data.count;
    return fallback === undefined ? list(data).length : fallback;
  }

  /** 商品主图：后端无图时回落到本地 SVG，绝不引用外链占位 */
  function image(item, variant) {
    var raw = item && (item.image || item.cover_image || item.main_image || item.thumb ||
      (Array.isArray(item.images) && item.images.length && (item.images[0].url || item.images[0].image || item.images[0])));
    if (raw && typeof raw === 'string' && raw.trim()) {
      if (/^(https?:)?\/\//.test(raw)) return raw;
      return raw.charAt(0) === '/' ? raw : '/' + raw;
    }
    return 'assets/img/product-' + ((variant || 0) % 5 + 1) + '.svg';
  }

  function money(v) {
    var n = Number(v || 0);
    return (Math.round(n * 100) / 100).toFixed(2);
  }

  global.YXApi = {
    request: request,
    list: list,
    total: total,
    image: image,
    money: money,
    getToken: getToken,
    setToken: setToken,
    getStoredUser: getStoredUser,
    setStoredUser: setStoredUser,
    logout: logout,
    apiBase: apiBase,

    // 商品
    items: function (params) { return request('/item/list', { params: params || { page: 1, page_size: 20 } }); },
    itemHot: function (params) { return request('/item/hot', { params: params || { page: 1, limit: 10 } }); },
    itemNew: function (params) { return request('/item/new', { params: params || { page: 1, limit: 10 } }); },
    itemRecommended: function (params) { return request('/item/recommended', { params: params || { page: 1, limit: 10 } }); },
    itemDetail: function (id) { return request('/item/detail', { params: { id: id } }); },
    categoryTree: function () { return request('/category/tree'); },
    categoryItems: function (categoryId, params) {
      return request('/category/items', { params: Object.assign({ category_id: categoryId, page: 1, page_size: 20 }, params || {}) });
    },
    searchHotWords: function () { return request('/config/search-hot-words'); },

    // 账号
    login: function (username, password) { return request('/auth/login', { method: 'POST', body: { username: username, password: password } }); },
    register: function (username, password, nickname) {
      return request('/auth/register', { method: 'POST', body: { username: username, password: password, nickname: nickname || username } });
    },
    userInfo: function () { return request('/user/info'); },

    // 购物车 / 结算
    cartList: function () { return request('/cart/list'); },
    cartCount: function () { return request('/cart/count'); },
    cartAdd: function (itemId, quantity, specId) {
      return request('/cart/add', { method: 'POST', body: { item_id: String(itemId), quantity: quantity || 1, spec_id: specId || 0 } });
    },
    cartUpdate: function (cartId, quantity) { return request('/cart/update', { method: 'POST', body: { cart_id: String(cartId), quantity: quantity } }); },
    cartRemove: function (cartId) { return request('/cart/remove', { method: 'POST', body: { cart_id: String(cartId) } }); },
    cartSelect: function (cartId, selected) { return request('/cart/select', { method: 'POST', body: { cart_ids: [String(cartId)], is_selected: selected ? 1 : 0 } }); },
    cartSelectAll: function (selected) { return request('/cart/select-all', { method: 'POST', body: { is_selected: selected ? 1 : 0 } }); },
    checkoutPreview: function () { return request('/cart/checkout-preview'); },

    // 地址 / 订单
    addressList: function () { return request('/address/list'); },
    addressAdd: function (data) { return request('/address/add', { method: 'POST', body: data }); },
    orderCreate: function (data) { return request('/order/create', { method: 'POST', body: data }); },
    orderList: function (params) { return request('/order/list', { params: params || { page: 1, page_size: 20 } }); },
    orderDetail: function (id) { return request('/order/detail', { params: { id: id } }); },
    orderCancel: function (id) { return request('/order/cancel', { method: 'POST', body: { order_id: String(id) } }); },
    paymentCreate: function (orderId, method) {
      return request('/payment/create', { method: 'POST', body: { order_id: String(orderId), payment_method: method || 'wechat' } });
    }
  };
})(window);
