/* 内联 SVG 图标集（无 emoji、无外链） */
(function (global) {
  'use strict';
  var P = {
    home: '<path d="M3 10.5 12 3l9 7.5"/><path d="M5.5 9.5V21h13V9.5"/><path d="M9.5 21v-6h5v6"/>',
    category: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    cart: '<path d="M3 4h2l2.2 11.2a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.5L20 7H6"/><circle cx="9.5" cy="20" r="1.4"/><circle cx="17" cy="20" r="1.4"/>',
    me: '<circle cx="12" cy="8" r="4"/><path d="M4.5 21a7.5 7.5 0 0 1 15 0"/>',
    search: '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m15.5 15.5 4.5 4.5"/>',
    back: '<path d="m14.5 5-7 7 7 7"/>',
    arrow: '<path d="m9 5 7 7-7 7"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    minus: '<path d="M5 12h14"/>',
    check: '<path d="m4.5 12.5 5 5 10-11"/>',
    location: '<path d="M12 21s7-5.6 7-11a7 7 0 1 0-14 0c0 5.4 7 11 7 11Z"/><circle cx="12" cy="10" r="2.5"/>',
    order: '<rect x="5" y="3" width="14" height="18" rx="2"/><path d="M8.5 8h7M8.5 12h7M8.5 16h4"/>',
    logout: '<path d="M10 4H5v16h5"/><path d="M14 8l4 4-4 4M18 12H9"/>',
    package: '<path d="M12 3 3 8v8l9 5 9-5V8l-9-5Z"/><path d="m3 8 9 5 9-5M12 13v8"/>',
    heart: '<path d="M12 20s-8-4.8-8-10.2A4.3 4.3 0 0 1 12 7a4.3 4.3 0 0 1 8 2.8C20 15.2 12 20 12 20Z"/>',
    trash: '<path d="M4 7h16M9 7V4h6v3M6 7l1 13h10l1-13"/>',
    refresh: '<path d="M20 11a8 8 0 0 0-14-5L4 8"/><path d="M4 4v4h4"/><path d="M4 13a8 8 0 0 0 14 5l2-2"/><path d="M20 20v-4h-4"/>',
    lock: '<rect x="4" y="10" width="16" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
    user: '<circle cx="12" cy="8" r="3.6"/><path d="M5 21a7 7 0 0 1 14 0"/>',
    wallet: '<rect x="3" y="6" width="18" height="14" rx="2"/><path d="M3 10h18M16 15h2"/>',
    empty: '<circle cx="12" cy="12" r="9"/><path d="M8.5 14.5h7M9 9.5h.01M15 9.5h.01"/>',
    filter: '<path d="M4 6h16M7 12h10M10 18h4"/>'
  };

  global.YXIcon = {
    get: function (name, size, cls) {
      var d = P[name] || P.package;
      return '<svg class="' + (cls || '') + '" viewBox="0 0 24 24" width="' + (size || 22) + '" height="' + (size || 22) +
        '" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + d + '</svg>';
    },
    fillTabs: function () {
      document.querySelectorAll('.tab-icon').forEach(function (el) {
        el.innerHTML = YXIcon.get(el.getAttribute('data-icon'), 22);
      });
    }
  };
})(window);
