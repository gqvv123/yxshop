const imageUtil = require('../../utils/image');

Component({
  properties: {
    item: { type: Object, value: null },
    action: { type: String, value: 'cart' },
    visible: { type: Boolean, value: false },
  },

  data: {
    specDimensions: [],
    skuList: [],
    selectedMap: {},
    currentSku: null,
    currentPrice: '',
    currentStock: 0,
    currentImage: '',
    quantity: 1,
    specComplete: false,
  },

  observers: {
    'item'(val) {
      if (!val) return;
      this.initSpecDimensions(val);
    },
    'visible'(val) {
      if (val && this.data.item) {
        this.initSpecDimensions(this.data.item);
      }
    },
  },

  methods: {
    initSpecDimensions(item) {
      const skus = item.skus || item.spec_prices || [];
      if (!skus.length || (skus.length === 1 && (!skus[0].spec_values || !Object.keys(skus[0].spec_values).length))) {
        const sku = skus[0] || {};
        const skuImage = skus.length === 1 ? imageUtil.resolve(sku.image || '') : '';
      const itemImage = imageUtil.getItemCover(item);
      this.setData({
          specDimensions: [],
          skuList: skus,
          selectedMap: {},
          currentSku: skus.length === 1 ? sku : null,
          currentImage: skuImage || itemImage,
          currentPrice: skus.length === 1 ? (sku.price || item.price || item.sale_price || '0.00') : (item.price || item.sale_price || '0.00'),
          currentStock: skus.length === 1 ? (sku.store_count || sku.stock || item.stock || 0) : (item.stock || 0),
          quantity: 1,
          specComplete: skus.length <= 1,
        });
        return;
      }

      const dimMap = {};
      skus.forEach((sku) => {
        const sv = sku.spec_values || {};
        Object.keys(sv).forEach((key) => {
          if (!dimMap[key]) dimMap[key] = new Set();
          dimMap[key].add(sv[key]);
        });
      });

      const specDimensions = Object.keys(dimMap).map((name) => ({
        name,
        values: Array.from(dimMap[name]).map((v) => ({ label: v, available: true })),
      }));

      this.setData({
        specDimensions,
        skuList: skus,
        selectedMap: {},
        currentSku: null,
        currentImage: imageUtil.getItemCover(item),
        currentPrice: item.price || item.sale_price || '0.00',
        currentStock: item.stock || 0,
        quantity: 1,
        specComplete: false,
      });
    },

    onSpecTap(e) {
      const { name, value } = e.currentTarget.dataset;
      const selectedMap = { ...this.data.selectedMap };
      if (selectedMap[name] === value) {
        delete selectedMap[name];
      } else {
        selectedMap[name] = value;
      }
      this.setData({ selectedMap });
      this.filterSkus();
    },

    filterSkus() {
      const { skuList, selectedMap, specDimensions } = this.data;
      const selectedKeys = Object.keys(selectedMap);

      const matched = skuList.filter((sku) => {
        const sv = sku.spec_values || {};
        return selectedKeys.every((k) => sv[k] === selectedMap[k]);
      });

      this.updateAvailability(matched);

      if (selectedKeys.length === specDimensions.length && matched.length === 1) {
        const sku = matched[0];
        const skuImg = imageUtil.resolve(sku.image || '');
        this.setData({
          currentSku: sku,
          currentImage: skuImg || imageUtil.getItemCover(this.data.item),
          currentPrice: sku.price,
          currentStock: sku.store_count || sku.stock || 0,
          specComplete: true,
          quantity: 1,
        });
      } else {
        let price = this.data.item.price || this.data.item.sale_price || '0.00';
        let stock = 0;
        if (matched.length) {
          const prices = matched.map((s) => Number(s.price || 0));
          const minP = Math.min(...prices);
          const maxP = Math.max(...prices);
          price = minP === maxP ? String(minP) : `${minP}-${maxP}`;
          stock = matched.reduce((sum, s) => sum + (s.store_count || s.stock || 0), 0);
        }
        this.setData({
          currentSku: null,
          currentPrice: price,
          currentStock: stock,
          specComplete: false,
        });
      }
    },

    updateAvailability(matched) {
      const { skuList, selectedMap, specDimensions } = this.data;
      const newDims = specDimensions.map((dim) => {
        const newValues = dim.values.map((v) => {
          if (selectedMap[dim.name] === v.label) {
            return { ...v, available: true };
          }
          const testSelect = { ...selectedMap, [dim.name]: v.label };
          const testKeys = Object.keys(testSelect);
          const hasMatch = skuList.some((sku) => {
            const sv = sku.spec_values || {};
            return testKeys.every((k) => sv[k] === testSelect[k]) && (sku.store_count || sku.stock || 0) > 0;
          });
          return { ...v, available: hasMatch };
        });
        return { ...dim, values: newValues };
      });
      this.setData({ specDimensions: newDims });
    },

    onChangeQty(e) {
      const delta = Number(e.currentTarget.dataset.delta);
      const qty = Math.max(1, Math.min(this.data.currentStock || 9999, this.data.quantity + delta));
      this.setData({ quantity: qty });
    },

    onConfirm() {
      if (!this.data.specComplete && this.data.specDimensions.length > 0) {
        wx.showToast({ title: '请选择完整规格', icon: 'none' });
        return;
      }
      const sku = this.data.currentSku;
      this.triggerEvent('confirm', {
        sku_id: sku ? (sku.id || sku.sku_id) : 0,
        spec_key: sku ? (sku.spec_key || '') : '',
        spec_key_name: sku ? (sku.spec_key_name || '') : '',
        price: this.data.currentPrice,
        stock: this.data.currentStock,
        quantity: this.data.quantity,
        action: this.data.action,
      });
    },

    onClose() {
      this.triggerEvent('close');
    },

    // 空方法：仅用于 catchtap 阻止事件冒泡到遮罩层
    noop() {},
  },
});