/* 盈锐商贸 H5 · Hash SPA，全部数据来自 /api/v1 真实接口 */
(function () {
  'use strict';

  var A = window.YXApi, I = window.YXIcon;
  var main = document.getElementById('h5Main');
  var toastEl = document.getElementById('toast');
  var badge = document.getElementById('cartBadge');
  var toastTimer;

  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  }
  function toast(msg) {
    toastEl.textContent = msg;
    toastEl.classList.add('show');
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { toastEl.classList.remove('show'); }, 3000);
  }
  function money(v) { return A.money(v); }
  function attr(s) { return esc(s); }
  function go(hash) { location.hash = hash; }

  window.YXShop = {
    onUnauthorized: function () {
      if (location.hash.indexOf('/login') < 0) go('#/login');
    }
  };

  function parseHash() {
    var raw = location.hash.replace(/^#\/?/, '') || 'home';
    var q = raw.indexOf('?');
    var path = q >= 0 ? raw.slice(0, q) : raw;
    var params = {};
    if (q >= 0) new URLSearchParams(raw.slice(q + 1)).forEach(function (v, k) { params[k] = v; });
    return { name: path.split('/')[0] || 'home', params: params };
  }

  function skeleton(n, cls) {
    var out = '';
    for (var i = 0; i < (n || 4); i++) out += '<div class="skeleton ' + (cls || 'skeleton-card') + '"></div>';
    return '<div class="goods-grid">' + out + '</div>';
  }
  function stateBox(title, desc, retry) {
    return '<div class="h5-state"><div class="state"><strong>' + esc(title) + '</strong><span>' + esc(desc) + '</span>' +
      (retry ? '<div class="state-action"><button class="btn small" data-act="retry">重新加载</button></div>' : '') +
      '</div></div>';
  }
  function emptyBox(title, desc, action, href) {
    return '<div class="h5-state"><div class="state"><strong>' + esc(title) + '</strong><span>' + esc(desc) + '</span>' +
      (action ? '<div class="state-action"><a class="btn small" href="' + attr(href) + '">' + esc(action) + '</a></div>' : '') +
      '</div></div>';
  }

  /* ---------- 商品卡片 ---------- */
  function goodsCard(item, i) {
    var id = item.id || item.item_id;
    var name = item.name || item.item_name || '未命名商品';
    var price = item.price != null ? item.price : (item.sale_price || 0);
    return '<a class="goods-card" href="#/detail?id=' + encodeURIComponent(id) + '">' +
      '<div class="thumb"><img src="' + attr(A.image(item, i)) + '" alt="' + attr(name) + '" loading="lazy" /></div>' +
      '<div class="info"><div class="name">' + esc(name) + '</div>' +
      '<div class="row"><span class="price">' + money(price) + '</span><small>库存 ' + esc(item.stock == null ? '—' : item.stock) + '</small></div>' +
      '</div></a>';
  }
  function goodsGrid(items) {
    return '<div class="goods-grid">' + items.map(goodsCard).join('') + '</div>';
  }

  /* ---------- 首页 ---------- */
  function renderHome(p) {
    main.innerHTML = '<div class="page">' +
      '<div class="search-row"><div class="search-box">' + I.get('search', 18) +
      '<input id="searchInput" placeholder="搜索商品名称" aria-label="搜索商品" /></div>' +
      '<button class="search-btn" data-act="search">搜索</button></div>' +
      '<div class="banner"><div class="banner-text">' +
        '<span class="kicker">盈锐商贸 · 移动版</span>' +
        '<strong>挑好东西，<br />不用等。</strong>' +
        '<small>商品、库存与价格实时同步</small>' +
      '</div><div class="banner-art"><img src="assets/img/product-1.svg" alt="" /></div></div>' +
      '<div class="chip-scroll" id="catChips"></div>' +
      '<div class="section-title"><h2>为你推荐</h2><span id="itemCount"></span></div>' +
      '<div id="homeGoods">' + skeleton(4) + '</div>' +
      '</div>';

    var keyword = p.params.keyword || '';
    if (keyword) document.getElementById('searchInput').value = keyword;

    Promise.all([
      A.categoryTree().catch(function () { return []; }),
      A.items({ page: 1, page_size: 20, keyword: keyword || undefined })
        .catch(function () { return A.itemRecommended({ page: 1, limit: 20 }); })
    ]).then(function (res) {
      var cats = A.list(res[0]);
      var chips = document.getElementById('catChips');
      if (chips) {
        chips.innerHTML = '<a class="chip active" href="#/category">全部分类</a>' +
          cats.slice(0, 8).map(function (c) {
            return '<a class="chip" href="#/category?cat=' + encodeURIComponent(c.id) + '">' + esc(c.name) + '</a>';
          }).join('');
      }
      var data = res[1];
      var items = A.list(data).filter(function (x) { return Number(x.is_on_sale || 0) !== 0 || x.is_on_sale === undefined; }).slice(0, 20);
      var box = document.getElementById('homeGoods');
      var cnt = document.getElementById('itemCount');
      if (cnt) cnt.textContent = '共 ' + A.total(data, items.length) + ' 件';
      if (!box) return;
      box.innerHTML = items.length ? goodsGrid(items)
        : emptyBox(keyword ? '没有找到相关商品' : '暂时没有在售商品', '换个关键词，或稍后再来看看。', '查看分类', '#/category');
    }).catch(function (e) {
      var box = document.getElementById('homeGoods');
      if (box) box.innerHTML = stateBox('商品加载失败', e.message || '后端服务暂不可用', true);
    });
  }

  /* ---------- 分类 ---------- */
  function renderCategory(p) {
    main.innerHTML = '<div class="page"><div class="page-head"><h1>商品分类</h1><span class="sub">按分类浏览在售商品</span></div>' +
      '<div class="cat-layout"><div class="cat-rail" id="catRail"><div class="skeleton" style="height:36px;margin-bottom:8px"></div><div class="skeleton" style="height:36px"></div></div>' +
      '<div class="cat-content" id="catGoods">' + skeleton(4) + '</div></div></div>';

    A.categoryTree().then(function (data) {
      var cats = A.list(data);
      var rail = document.getElementById('catRail');
      var active = p.params.cat || (cats[0] && cats[0].id) || '';
      if (!rail) return;
      rail.innerHTML = '<button class="' + (!p.params.cat ? 'active' : '') + '" data-act="cat" data-id="">全部</button>' +
        cats.map(function (c) {
          return '<button class="' + (String(c.id) === String(active) && p.params.cat ? 'active' : '') + '" data-act="cat" data-id="' + attr(c.id) + '">' + esc(c.name) + '</button>';
        }).join('');
      loadCatGoods(active);
    }).catch(function (e) {
      var rail = document.getElementById('catRail');
      if (rail) rail.innerHTML = '';
      var box = document.getElementById('catGoods');
      if (box) box.innerHTML = stateBox('分类加载失败', e.message || '后端服务暂不可用', true);
    });
  }

  function loadCatGoods(catId) {
    var box = document.getElementById('catGoods');
    if (!box) return;
    box.innerHTML = skeleton(4);
    var req = catId ? A.categoryItems(catId, { page: 1, page_size: 20 }) : A.items({ page: 1, page_size: 20 });
    req.then(function (data) {
      var items = A.list(data).slice(0, 20);
      box.innerHTML = items.length ? goodsGrid(items) : emptyBox('该分类下暂无商品', '去别的分类看看吧。', '全部商品', '#/category');
    }).catch(function (e) {
      box.innerHTML = stateBox('商品加载失败', e.message || '后端服务暂不可用', true);
    });
  }

  /* ---------- 商品详情 ---------- */
  function renderDetail(p) {
    var id = p.params.id;
    if (!id) { main.innerHTML = '<div class="page">' + emptyBox('缺少商品编号', '请从商品列表进入。', '回到首页', '#/home') + '</div>'; return; }
    main.innerHTML = '<div class="page"><div class="page-head"><button class="back-btn" data-act="back" aria-label="返回">' + I.get('back', 18) + '</button><h1>商品详情</h1></div>' +
      '<div class="skeleton" style="aspect-ratio:1/1;border-radius:20px"></div></div>';

    A.itemDetail(id).then(function (item) {
      var price = item.price != null ? item.price : (item.sale_price || 0);
      var name = item.name || '未命名商品';
      main.innerHTML = '<div class="page">' +
        '<div class="page-head"><button class="back-btn" data-act="back" aria-label="返回">' + I.get('back', 18) + '</button><h1>商品详情</h1></div>' +
        '<div class="detail-img"><img src="' + attr(A.image(item, 0)) + '" alt="' + attr(name) + '" /></div>' +
        '<div class="detail-block"><h1>' + esc(name) + '</h1>' +
          '<div class="price detail-price">' + money(price) + '</div>' +
          '<div class="detail-meta"><span>库存 ' + esc(item.stock == null ? '—' : item.stock) + ' 件</span><span>销量 ' + esc(item.total_sales || 0) + '</span></div>' +
          '<div class="detail-desc">' + esc(item.summary || item.description || '暂无商品介绍。上架信息由后台维护，价格与库存实时同步。') + '</div>' +
          '<div class="qty-row"><span style="font-size:13px;color:var(--muted)">购买数量</span>' +
            '<div class="stepper"><button data-act="qty-minus" aria-label="减少">−</button><span id="detailQty">1</span><button data-act="qty-plus" aria-label="增加">+</button></div>' +
          '</div></div>' +
        '<div class="action-bar"><button class="btn ghost" data-act="add-cart" data-id="' + attr(id) + '">加入购物车</button>' +
          '<button class="btn" data-act="buy-now" data-id="' + attr(id) + '">立即购买</button></div>' +
        '</div>';
    }).catch(function (e) {
      main.innerHTML = '<div class="page">' + stateBox('商品不存在或已下架', e.message || '请返回列表重新选择', false) +
        '<div style="margin-top:14px"><a class="btn ghost block" href="#/home">返回首页</a></div></div>';
    });
  }

  /* ---------- 购物车 ---------- */
  function renderCart() {
    if (!A.getToken()) return go('#/login?next=/cart');
    main.innerHTML = '<div class="page"><div class="page-head"><h1>购物车</h1><span class="sub">勾选商品后结算</span></div><div id="cartBox">' + skeleton(3) + '</div></div>';

    A.cartList().then(function (data) {
      var items = (data && data.items) ? data.items : A.list(data);
      var box = document.getElementById('cartBox');
      if (!box) return;
      if (!items.length) {
        box.innerHTML = emptyBox('购物车还是空的', '挑几件喜欢的商品吧。', '去逛逛', '#/home');
        return;
      }
      var selected = items.filter(function (x) { return Number(x.is_selected) === 1; });
      var sum = selected.reduce(function (t, x) { return t + Number(x.price || 0) * Number(x.quantity || 1); }, 0);
      box.innerHTML = items.map(function (x, i) {
        var cid = x.id || x.cart_id;
        var iid = x.item_id;
        var on = Number(x.is_selected) === 1;
        return '<div class="cart-row">' +
          '<button class="checkbox ' + (on ? 'on' : '') + '" data-act="select" data-id="' + attr(cid) + '" data-on="' + (on ? 1 : 0) + '" aria-label="选择">' + I.get('check', 13) + '</button>' +
          '<a class="thumb" href="#/detail?id=' + attr(iid) + '"><img src="' + attr(A.image(x, i)) + '" alt="" /></a>' +
          '<div class="body"><div class="name">' + esc(x.item_name || x.name || '商品') + '</div>' +
            '<div class="price">' + money(x.price) + '</div>' +
            '<div class="bottom"><div class="stepper">' +
              '<button data-act="qty" data-id="' + attr(cid) + '" data-delta="-1">−</button><span>' + esc(x.quantity) + '</span>' +
              '<button data-act="qty" data-id="' + attr(cid) + '" data-delta="1">+</button></div>' +
              '<button class="trash" data-act="remove" data-id="' + attr(cid) + '" aria-label="删除">' + I.get('trash', 17) + '</button></div>' +
          '</div></div>';
      }).join('') +
      '<div class="checkout-bar"><div class="sum">已选 ' + selected.length + ' 件<strong class="price">' + money(sum) + '</strong></div>' +
        '<button class="btn" data-act="to-checkout" ' + (selected.length ? '' : 'disabled') + '>去结算</button></div>';
      refreshBadge();
    }).catch(function (e) {
      var box = document.getElementById('cartBox');
      if (box) box.innerHTML = stateBox('购物车加载失败', e.message || '后端服务暂不可用', true);
    });
  }

  /* ---------- 结算 ---------- */
  function renderCheckout() {
    if (!A.getToken()) return go('#/login?next=/checkout');
    main.innerHTML = '<div class="page"><div class="page-head"><button class="back-btn" data-act="back">' + I.get('back', 18) + '</button><h1>确认订单</h1></div><div id="checkoutBox">' + skeleton(3) + '</div></div>';

    Promise.all([A.checkoutPreview(), A.addressList().catch(function () { return []; })])
      .then(function (res) {
        var pre = res[0] || {}, addrs = A.list(res[1]);
        var items = pre.selected_items || [];
        var addr = addrs.filter(function (a) { return Number(a.is_default) === 1; })[0] || addrs[0];
        var box = document.getElementById('checkoutBox');
        if (!box) return;
        if (!items.length) {
          box.innerHTML = emptyBox('没有待结算的商品', '请先在购物车里勾选商品。', '去购物车', '#/cart');
          return;
        }
        var addrHtml = addr
          ? '<div class="address-card"><span class="loc">' + I.get('location', 20) + '</span><div style="flex:1">' +
              '<strong>' + esc(addr.name) + '　' + esc(addr.phone) + '</strong>' +
              '<p>' + esc([addr.province_name || addr.province, addr.city_name || addr.city, addr.district_name || addr.district, addr.detail].filter(Boolean).join(' ')) + '</p>' +
            '</div></div>'
          : '<div class="address-card"><span class="loc">' + I.get('location', 20) + '</span><div style="flex:1"><strong>还没有收货地址</strong><p>填写地址后才能提交订单</p>' +
            '<button class="link-btn" data-act="add-address">新增收货地址</button></div></div>';

        var goodsHtml = items.map(function (x, i) {
          return '<div class="cart-row" style="margin-bottom:8px"><div class="thumb"><img src="' + attr(A.image(x, i)) + '" alt="" /></div>' +
            '<div class="body"><div class="name">' + esc(x.item_name || x.name || '商品') + '</div>' +
            '<div class="price">' + money(x.price) + '</div><div class="bottom"><small style="color:var(--muted)">数量 ' + esc(x.quantity) + '</small></div></div></div>';
        }).join('');

        box.innerHTML = addrHtml +
          '<div class="form-card" style="margin-top:14px"><h3>商品清单</h3>' + goodsHtml + '</div>' +
          '<div class="form-card"><h3>费用明细</h3>' +
            '<div class="amount-line"><span>商品金额</span><span>' + money(pre.goods_amount) + '</span></div>' +
            '<div class="amount-line"><span>运费</span><span>' + money(pre.shipping_fee) + '</span></div>' +
            '<div class="amount-line"><span>优惠</span><span>- ' + money(pre.coupon_discount || 0) + '</span></div>' +
            '<div class="amount-line total"><span>应付</span><span class="price">' + money(pre.estimated_pay) + '</span></div>' +
          '</div>' +
          '<div class="form-card"><div class="field"><label>订单备注（选填）</label><input id="orderRemark" placeholder="例如：工作日送达" /></div></div>' +
          '<div class="checkout-bar"><div class="sum">合计<strong class="price">' + money(pre.estimated_pay) + '</strong></div>' +
            '<button class="btn" data-act="submit-order" ' + (addr ? '' : 'disabled') + '>提交订单</button></div>';
        box.__checkout = { items: items, addressId: addr && (addr.id || addr.address_id) };
      }).catch(function (e) {
        var box = document.getElementById('checkoutBox');
        if (box) box.innerHTML = stateBox('结算信息加载失败', e.message || '后端服务暂不可用', true);
      });
  }

  function submitOrder() {
    var box = document.getElementById('checkoutBox');
    var ctx = box && box.__checkout;
    if (!ctx || !ctx.addressId) return toast('请先填写收货地址');
    var btn = document.querySelector('[data-act="submit-order"]');
    if (btn) { btn.disabled = true; btn.textContent = '提交中…'; }
    var items = ctx.items.map(function (x) {
      return { item_id: String(x.item_id), quantity: Number(x.quantity) || 1, spec_id: x.spec_id || 0 };
    });
    var remark = document.getElementById('orderRemark');
    A.orderCreate({ address_id: String(ctx.addressId), items: items, remark: remark ? remark.value : '' })
      .then(function (order) {
        var id = order && (order.id || order.order_id);
        // 清空已下单的购物车条目（尽力而为，不影响下单结果）
        Promise.all(ctx.items.map(function (x) {
          return A.cartRemove(x.id || x.cart_id).catch(function () { return null; });
        })).then(refreshBadge);
        toast('订单创建成功');
        setTimeout(function () { go(id ? '#/orders' : '#/orders'); }, 500);
      }).catch(function (e) {
        toast(e.message || '下单失败');
        if (btn) { btn.disabled = false; btn.textContent = '提交订单'; }
      });
  }

  /* ---------- 订单 ---------- */
  function orderStatus(o) {
    if (Number(o.status) === 0) return '已取消';
    if (Number(o.pay_status) === 10) return '待支付';
    if (Number(o.delivery_status) === 20) return '待收货';
    if (Number(o.status) === 30 || Number(o.status) === 40) return '已完成';
    return '待发货';
  }
  function renderOrders() {
    if (!A.getToken()) return go('#/login?next=/orders');
    main.innerHTML = '<div class="page"><div class="page-head"><h1>我的订单</h1><span class="sub">下单记录实时同步</span></div><div id="orderBox">' + skeleton(3) + '</div></div>';
    A.orderList({ page: 1, page_size: 20 }).then(function (data) {
      var list = A.list(data);
      var box = document.getElementById('orderBox');
      if (!box) return;
      if (!list.length) { box.innerHTML = emptyBox('还没有订单', '下单后可以在这里查看进度。', '去挑商品', '#/home'); return; }
      box.innerHTML = list.map(function (o) {
        var goods = (o.items || o.goods || []).slice(0, 2).map(function (g, i) {
          return '<div class="goods"><img src="' + attr(A.image(g, i)) + '" alt="" /><div class="name">' + esc(g.name || g.item_name || g.goods_name || '商品') + '</div><small style="color:var(--muted)">×' + esc(g.total_num || g.quantity || 1) + '</small></div>';
        }).join('');
        return '<a class="order-card" href="#/order-detail?id=' + attr(o.id) + '">' +
          '<div class="top"><span>' + esc(o.order_no || o.id) + '</span><span class="status">' + esc(orderStatus(o)) + '</span></div>' +
          goods +
          '<div class="foot"><span>共 ' + esc((o.items || o.goods || []).length) + ' 种商品</span><span class="price">' + money(o.pay_price || o.total_price) + '</span></div>' +
        '</a>';
      }).join('');
    }).catch(function (e) {
      var box = document.getElementById('orderBox');
      if (box) box.innerHTML = stateBox('订单加载失败', e.message || '后端服务暂不可用', true);
    });
  }

  function renderOrderDetail(p) {
    if (!A.getToken()) return go('#/login');
    main.innerHTML = '<div class="page"><div class="page-head"><button class="back-btn" data-act="back">' + I.get('back', 18) + '</button><h1>订单详情</h1></div><div id="odBox">' + skeleton(2) + '</div></div>';
    A.orderDetail(p.params.id).then(function (o) {
      var box = document.getElementById('odBox');
      if (!box) return;
      var address = o.address || {};
      var addrText = [address.province_name || address.province, address.city_name || address.city,
        address.district_name || address.district, address.detail].filter(Boolean).join(' ');
      box.innerHTML = '<div class="form-card"><h3>' + esc(orderStatus(o)) + '</h3>' +
          '<div class="amount-line"><span>订单号</span><span>' + esc(o.order_no || o.id) + '</span></div>' +
          '<div class="amount-line"><span>商品金额</span><span>' + money(o.total_price) + '</span></div>' +
          '<div class="amount-line"><span>运费</span><span>' + money(o.express_price) + '</span></div>' +
          '<div class="amount-line total"><span>实付</span><span class="price">' + money(o.pay_price) + '</span></div></div>' +
        '<div class="form-card"><h3>收货信息</h3><div class="amount-line"><span>' + esc(address.name || '—') + '　' + esc(address.phone || '') + '</span></div>' +
          '<p style="font-size:12px;color:var(--muted)">' + esc(addrText || '暂无地址信息') + '</p></div>' +
        '<a class="btn ghost block" href="#/orders" style="margin-top:8px">返回订单列表</a>';
    }).catch(function (e) {
      var box = document.getElementById('odBox');
      if (box) box.innerHTML = stateBox('订单详情加载失败', e.message || '请稍后再试', false);
    });
  }

  /* ---------- 地址 ---------- */
  function renderAddress() {
    if (!A.getToken()) return go('#/login');
    main.innerHTML = '<div class="page"><div class="page-head"><button class="back-btn" data-act="back">' + I.get('back', 18) + '</button><h1>收货地址</h1></div>' +
      '<div id="addrList">' + skeleton(2) + '</div>' +
      '<div class="form-card" style="margin-top:16px"><h3>新增地址</h3>' +
        '<div class="field-row"><div class="field"><label>收货人</label><input id="aName" placeholder="姓名" /></div>' +
        '<div class="field"><label>手机号</label><input id="aPhone" placeholder="11 位手机号" /></div></div>' +
        '<div class="field-row"><div class="field"><label>省</label><input id="aProvince" placeholder="广东省" /></div>' +
        '<div class="field"><label>市</label><input id="aCity" placeholder="深圳市" /></div></div>' +
        '<div class="field"><label>区 / 县</label><input id="aDistrict" placeholder="南山区" /></div>' +
        '<div class="field"><label>详细地址</label><input id="aDetail" placeholder="街道、门牌号" /></div>' +
        '<button class="btn block" data-act="save-address">保存地址</button></div></div>';
    loadAddresses();
  }
  function loadAddresses() {
    var box = document.getElementById('addrList');
    if (!box) return;
    A.addressList().then(function (data) {
      var list = A.list(data);
      box.innerHTML = list.length ? list.map(function (a) {
        return '<div class="address-card" style="margin-bottom:10px"><span class="loc">' + I.get('location', 20) + '</span><div style="flex:1">' +
          '<strong>' + esc(a.name) + '　' + esc(a.phone) + (Number(a.is_default) === 1 ? '　<span class="tag">默认</span>' : '') + '</strong>' +
          '<p>' + esc([a.province_name || a.province, a.city_name || a.city, a.district_name || a.district, a.detail].filter(Boolean).join(' ')) + '</p></div></div>';
      }).join('') : emptyBox('还没有地址', '在下方填写一个新的收货地址。', '', '');
    }).catch(function (e) {
      box.innerHTML = stateBox('地址加载失败', e.message || '请稍后再试', true);
    });
  }

  /* ---------- 我的 ---------- */
  function renderMe() {
    if (!A.getToken()) return go('#/login?next=/me');
    var cached = A.getStoredUser();
    main.innerHTML = '<div class="page"><div class="profile-card"><div class="avatar">' + esc((cached && (cached.nickname || cached.username) || '会').charAt(0)) + '</div>' +
      '<div><strong>' + esc(cached ? (cached.nickname || cached.username || '会员用户') : '会员用户') + '</strong>' +
      '<small>欢迎回来，数据实时同步</small></div></div>' +
      '<div class="menu-list">' +
        '<a href="#/orders"><span class="mi">' + I.get('order', 19) + '</span>我的订单<span class="arrow">' + I.get('arrow', 16) + '</span></a>' +
        '<a href="#/address"><span class="mi">' + I.get('location', 19) + '</span>收货地址<span class="arrow">' + I.get('arrow', 16) + '</span></a>' +
        '<a href="#/cart"><span class="mi">' + I.get('cart', 19) + '</span>购物车<span class="arrow">' + I.get('arrow', 16) + '</span></a>' +
        '<button data-act="logout"><span class="mi">' + I.get('logout', 19) + '</span>退出登录<span class="arrow">' + I.get('arrow', 16) + '</span></button>' +
      '</div>' +
      '<div class="form-card" style="margin-top:16px"><h3>关于本站</h3><p style="font-size:12px;color:var(--muted);line-height:1.8">移动 H5 与微信小程序、管理后台共用同一套商品与订单数据。当前接口地址：' +
        esc(A.apiBase() || '同源代理') + '</p></div></div>';
    A.userInfo().then(function (u) {
      if (u) { A.setStoredUser(u); }
    }).catch(function () {});
  }

  /* ---------- 登录 / 注册 ---------- */
  function renderLogin(p) {
    var next = p.params.next || '/home';
    main.innerHTML = '<div class="page"><div class="login-hero"><img src="assets/img/logo.svg" alt="" /><h1>登录盈锐商贸</h1><p>登录后即可加购、下单与查看订单</p></div>' +
      '<div class="tab-switch"><button class="active" data-act="auth-tab" data-mode="login">登录</button><button data-act="auth-tab" data-mode="register">注册</button></div>' +
      '<div class="form-card">' +
        '<div class="field"><label>账号</label><input id="authUser" placeholder="用户名 / 手机号" autocomplete="username" /></div>' +
        '<div class="field" id="nickField" hidden><label>昵称（选填）</label><input id="authNick" placeholder="显示名称" /></div>' +
        '<div class="field"><label>密码</label><input id="authPass" type="password" placeholder="至少 6 位" autocomplete="current-password" /></div>' +
        '<div class="form-error" id="authErr"></div>' +
        '<button class="btn block" data-act="auth-submit" data-mode="login" data-next="' + attr(next) + '">登录</button>' +
      '</div>' +
      '<p style="text-align:center;font-size:12px;color:var(--muted)">没有账号？点上方「注册」创建一个</p></div>';
  }

  /* ---------- 路由 ---------- */
  var ROUTES = {
    home: renderHome,
    category: renderCategory,
    detail: renderDetail,
    cart: renderCart,
    checkout: renderCheckout,
    orders: renderOrders,
    'order-detail': renderOrderDetail,
    address: renderAddress,
    me: renderMe,
    login: renderLogin
  };
  var TAB_OF = { home: 'home', category: 'category', detail: 'home', cart: 'cart',
    checkout: 'cart', orders: 'me', 'order-detail': 'me', address: 'me', me: 'me', login: 'me' };

  function render() {
    var r = parseHash();
    var fn = ROUTES[r.name] || renderHome;
    window.scrollTo(0, 0);
    fn(r);
    document.querySelectorAll('#tabbar a').forEach(function (a) {
      a.classList.toggle('active', a.getAttribute('data-tab') === (TAB_OF[r.name] || 'home'));
    });
    document.getElementById('tabbar').style.display = r.name === 'login' ? 'none' : '';
    if (A.getToken()) refreshBadge();
  }

  function refreshBadge() {
    if (!badge) return;
    A.cartCount().then(function (d) {
      var n = 0;
      if (typeof d === 'number') n = d;
      else if (d && typeof d === 'object') n = Number(d.count || d.total || d.quantity || 0);
      if (n > 0) { badge.textContent = n > 99 ? '99+' : String(n); badge.hidden = false; }
      else badge.hidden = true;
    }).catch(function () { badge.hidden = true; });
  }

  /* ---------- 事件委托 ---------- */
  document.addEventListener('click', function (ev) {
    var el = ev.target.closest('[data-act]');
    if (!el) return;
    var act = el.getAttribute('data-act');
    var id = el.getAttribute('data-id');

    if (act === 'back') { history.length > 1 ? history.back() : go('#/home'); return; }
    if (act === 'retry') { render(); return; }
    if (act === 'search') {
      var v = (document.getElementById('searchInput') || {}).value || '';
      go('#/home?keyword=' + encodeURIComponent(v.trim()));
      return;
    }
    if (act === 'cat') {
      document.querySelectorAll('#catRail button').forEach(function (b) { b.classList.remove('active'); });
      el.classList.add('active');
      loadCatGoods(id);
      return;
    }
    if (act === 'qty-minus' || act === 'qty-plus') {
      var q = document.getElementById('detailQty');
      var n = Math.max(1, Math.min(99, Number(q.textContent) + (act === 'qty-plus' ? 1 : -1)));
      q.textContent = String(n);
      return;
    }
    if (act === 'add-cart' || act === 'buy-now') {
      if (!A.getToken()) return go('#/login?next=/detail?id=' + encodeURIComponent(id));
      var qty = Number((document.getElementById('detailQty') || {}).textContent || 1);
      A.cartAdd(id, qty).then(function () {
        toast(act === 'add-cart' ? '已加入购物车' : '已加入购物车，去结算');
        refreshBadge();
        if (act === 'buy-now') setTimeout(function () { go('#/cart'); }, 350);
      }).catch(function (e) { toast(e.message || '加入购物车失败'); });
      return;
    }
    if (act === 'select') {
      A.cartSelect(id, el.getAttribute('data-on') !== '1').then(render).catch(function (e) { toast(e.message); });
      return;
    }
    if (act === 'qty') {
      var row = el.closest('.cart-row');
      var span = row.querySelector('.stepper span');
      var next = Math.max(1, Number(span.textContent) + Number(el.getAttribute('data-delta')));
      A.cartUpdate(id, next).then(render).catch(function (e) { toast(e.message); });
      return;
    }
    if (act === 'remove') {
      A.cartRemove(id).then(function () { toast('已移除'); render(); }).catch(function (e) { toast(e.message); });
      return;
    }
    if (act === 'to-checkout') { go('#/checkout'); return; }
    if (act === 'add-address') { go('#/address'); return; }
    if (act === 'submit-order') { submitOrder(); return; }
    if (act === 'save-address') {
      var val = function (k) { return (document.getElementById(k) || {}).value || ''; };
      var payload = {
        name: val('aName').trim(), phone: val('aPhone').trim(),
        province: val('aProvince').trim(), city: val('aCity').trim(),
        district: val('aDistrict').trim(), detail: val('aDetail').trim(), is_default: 1
      };
      if (!payload.name || !payload.phone || !payload.detail) return toast('请填写收货人、手机号和详细地址');
      A.addressAdd(payload).then(function () { toast('地址已保存'); loadAddresses(); })
        .catch(function (e) { toast(e.message || '保存失败'); });
      return;
    }
    if (act === 'auth-tab') {
      var mode = el.getAttribute('data-mode');
      document.querySelectorAll('[data-act="auth-tab"]').forEach(function (b) { b.classList.toggle('active', b === el); });
      document.getElementById('nickField').hidden = mode !== 'register';
      var sub = document.querySelector('[data-act="auth-submit"]');
      sub.setAttribute('data-mode', mode);
      sub.textContent = mode === 'register' ? '创建账号' : '登录';
      document.getElementById('authErr').textContent = '';
      return;
    }
    if (act === 'auth-submit') {
      var mode2 = el.getAttribute('data-mode');
      var user = (document.getElementById('authUser').value || '').trim();
      var pass = document.getElementById('authPass').value || '';
      var nick = (document.getElementById('authNick').value || '').trim();
      var err = document.getElementById('authErr');
      if (!user || !pass) { err.textContent = '请填写账号和密码'; return; }
      if (mode2 === 'register' && pass.length < 6) { err.textContent = '密码至少 6 位'; return; }
      el.disabled = true; el.textContent = '请稍候…'; err.textContent = '';
      var req = mode2 === 'register' ? A.register(user, pass, nick) : A.login(user, pass);
      req.then(function (d) {
        A.setToken(d.token || d.access_token);
        A.setStoredUser(d.user || { username: user });
        toast(mode2 === 'register' ? '注册成功，已自动登录' : '登录成功');
        var nx = el.getAttribute('data-next') || '/home';
        setTimeout(function () { go('#' + (nx.charAt(0) === '/' ? nx : '/' + nx)); }, 350);
      }).catch(function (e) {
        err.textContent = e.message || '操作失败';
        el.disabled = false;
        el.textContent = mode2 === 'register' ? '创建账号' : '登录';
      });
      return;
    }
    if (act === 'logout') {
      A.logout(); badge.hidden = true; toast('已退出登录'); go('#/home');
      return;
    }
  });

  I.fillTabs();
  window.addEventListener('hashchange', render);
  render();
})();
