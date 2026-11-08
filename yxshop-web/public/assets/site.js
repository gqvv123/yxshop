/* PC 品牌官网 · 数据来自现有 /api/v1 真实接口 */
(function () {
  'use strict';

  var $ = function (s) { return document.querySelector(s); };
  var toastEl = $('#toast');
  var toastTimer;
  function toast(msg) {
    if (!toastEl) return;
    toastEl.textContent = msg;
    toastEl.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 3200);
  }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function stateBox(title, desc, actionText) {
    return '<div class="state" style="grid-column:1/-1">' +
      '<strong>' + esc(title) + '</strong><span>' + esc(desc) + '</span>' +
      (actionText ? '<div class="state-action"><button class="btn small" data-retry>重试</button></div>' : '') +
      '</div>';
  }

  function productCard(item, i) {
    var id = item.id || item.item_id;
    var name = item.name || item.item_name || '未命名商品';
    var price = item.price != null ? item.price : (item.sale_price || 0);
    var stock = item.stock != null ? item.stock : '—';
    return '<a class="product-card" href="h5.html#/detail?id=' + encodeURIComponent(id) + '">' +
      '<div class="thumb"><img src="' + YXApi.image(item, i) + '" alt="' + esc(name) + '" loading="lazy" /></div>' +
      '<div class="info">' +
        '<div class="name">' + esc(name) + '</div>' +
        '<div class="sub">库存 ' + esc(stock) + ' 件</div>' +
        '<div class="row"><span class="price">' + YXApi.money(price) + '</span><small>查看详情</small></div>' +
      '</div></a>';
  }

  function loadProducts() {
    var grid = $('#productGrid');
    if (!grid) return;
    Promise.all([
      YXApi.items({ page: 1, page_size: 8 }),
      YXApi.categoryTree().catch(function () { return []; })
    ]).then(function (res) {
      var data = res[0];
      var items = YXApi.list(data).slice(0, 8);
      var count = YXApi.total(data, items.length);
      var cats = YXApi.list(res[1]);

      var meta = $('#heroMeta');
      if (meta) {
        var vals = meta.querySelectorAll('strong');
        if (vals[0]) vals[0].textContent = String(count);
        if (vals[1]) vals[1].textContent = String(cats.length);
      }

      if (!items.length) {
        grid.innerHTML = stateBox('暂时没有在售商品', '后台上架商品后，这里会自动出现。', true);
        return;
      }
      grid.innerHTML = items.map(productCard).join('');
    }).catch(function (e) {
      grid.innerHTML = stateBox('商品数据未加载', e.message || '后端服务暂不可用', true);
      var meta = $('#heroMeta');
      if (meta) {
        var vals = meta.querySelectorAll('strong');
        if (vals[0]) vals[0].textContent = '—';
        if (vals[1]) vals[1].textContent = '—';
        if (vals[2]) vals[2].textContent = '未连接';
      }
      toast(e.message || '商品接口未连接');
    }).then(function () {
      var btn = grid.querySelector('[data-retry]');
      if (btn) btn.addEventListener('click', function () {
        grid.innerHTML = '<div class="product-card skeleton" style="height:320px"></div>'.repeat(4);
        loadProducts();
      });
    });
  }

  // 滚动进入动画
  function initReveal() {
    var nodes = document.querySelectorAll('.reveal');
    if (!('IntersectionObserver' in window)) {
      nodes.forEach(function (n) { n.classList.add('in'); });
      return;
    }
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (en) {
        if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); }
      });
    }, { threshold: 0.12, rootMargin: '0px 0px -40px 0px' });
    nodes.forEach(function (n, i) { n.style.transitionDelay = (i % 4) * 60 + 'ms'; io.observe(n); });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initReveal();
    loadProducts();
  });
})();
