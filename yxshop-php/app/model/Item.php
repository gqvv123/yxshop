<?php

namespace app\model;

class Item extends BaseModel
{
    protected $table = 'yxshop_items';

    protected $fillable = [
        'category_id', 'initial_sales', 'name', 'subtitle', 'click', 'brand_id', 'stock',
        'comment_count', 'weight', 'summary', 'description', 'is_physical', 'is_on_sale',
        'is_free_shipping', 'sort', 'is_recommended', 'is_new', 'is_hot', 'supports_coupon',
        'supports_vip', 'type_id', 'spec_type', 'points_reward', 'points_exchange',
        'total_sales', 'promotion_type', 'promotion_id', 'app_id', 'shipping_template_id', 'discount',
        'commission_type', 'commission_amount', 'supports_discount', 'member_level_id', 'services',
        'show_in_category', 'seo_title', 'seo_keywords', 'seo_description', 'video_url',
        'unit', 'is_virtual', 'virtual_type', 'sale_price', 'price', 'cost_price', 'stock_warning',
        'length', 'width', 'height', 'valid_days', 'usage_note', 'status',
    ];

    protected $casts = [
        'initial_sales' => 'integer',
        'click' => 'integer',
        'stock' => 'integer',
        'comment_count' => 'integer',
        'weight' => 'integer',
        'total_sales' => 'integer',
        'is_physical' => 'integer',
        'is_on_sale' => 'integer',
        'is_free_shipping' => 'integer',
        'is_recommended' => 'integer',
        'is_new' => 'integer',
        'is_hot' => 'integer',
        'supports_coupon' => 'integer',
        'supports_vip' => 'integer',
        'supports_discount' => 'integer',
        'show_in_category' => 'integer',
        'is_virtual' => 'integer',
        'points_reward' => 'decimal:2',
        'points_exchange' => 'integer',
        'discount' => 'decimal:2',
        'commission_amount' => 'string',
        'member_level_id' => 'integer',
        'shipping_template_id' => 'integer',
        'promotion_type' => 'integer',
        'promotion_id' => 'integer',
        'sale_price' => 'decimal:2',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'stock_warning' => 'integer',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'valid_days' => 'integer',
        'status' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    const STATUS_OFF_SALE = 0;
    const STATUS_ON_SALE = 1;

    const TYPE_REAL = 1;
    const TYPE_VIRTUAL = 0;

    const PROM_TYPE_NORMAL = 0;
    const PROM_TYPE_FLASH = 1;
    const PROM_TYPE_GROUP = 2;
    const PROM_TYPE_DISCOUNT = 3;

    protected $with = [];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id', 'id');
    }

    public function images()
    {
        return $this->hasMany(ItemImage::class, 'item_id', 'id');
    }

    public function specPrices()
    {
        return $this->hasMany(ItemSpecPrice::class, 'item_id', 'id');
    }

    public function skus()
    {
        return $this->hasMany(ItemSpecPrice::class, 'item_id', 'id');
    }

    public function specs()
    {
        return $this->hasMany(ItemSpecPrice::class, 'item_id', 'id');
    }

    public function attrs()
    {
        return $this->hasMany(ItemAttr::class, 'item_id', 'id');
    }

    public function tags()
    {
        return $this->belongsToMany(ItemTag::class, 'yxshop_item_tag_relations', 'item_id', 'tag_id');
    }

    public function favorites()
    {
        return $this->hasMany(ItemFavorite::class, 'item_id', 'id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'item_id', 'id');
    }

    public function views()
    {
        return $this->hasMany(ItemView::class, 'item_id', 'id');
    }

    public function searches()
    {
        return $this->hasMany(ItemSearch::class, 'item_id', 'id');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class, 'item_id', 'id');
    }

    public function stockWarnings()
    {
        return $this->hasMany(ItemStockWarning::class, 'item_id', 'id');
    }

    public function salesStats()
    {
        return $this->hasMany(ItemSpecSales::class, 'item_id', 'id');
    }

    public function priceHistories()
    {
        return $this->hasMany(ItemSpecPriceHistory::class, 'item_id', 'id');
    }

    public function stockLogs()
    {
        return $this->hasMany(ItemSpecStockLog::class, 'item_id', 'id');
    }

    public function shippingTemplate()
    {
        return $this->belongsTo(Delivery::class, 'shipping_template_id', 'id');
    }

    public function getVideoAttribute()
    {
        return $this->video_url;
    }

    public function getMainImageAttribute()
    {
        if ($this->relationLoaded('images')) {
            $image = $this->getRelation('images')->first();
        } else {
            $image = $this->images()->first();
        }
        if (!$image) return '';
        // 优先取 url 字段（存储的是文件路径），降级到 image_id
        $url = $image->url;
        if ($url && $url !== '0') return $url;
        $imageId = $image->image_id;
        if ($imageId && $imageId !== '0') return $imageId;
        return '';
    }

    public function getTotalSalesAttribute()
    {
        return ($this->attributes['initial_sales'] ?? 0) + ($this->attributes['total_sales'] ?? 0);
    }

    public function getRatingAttribute()
    {
        if ($this->relationLoaded('comments')) {
            $comments = $this->getRelation('comments')->where('status', 1);
        } else {
            $comments = $this->comments()->where('status', 1)->get();
        }
        if ($comments->isEmpty()) {
            return 0;
        }
        return $comments->avg('score') / 10;
    }

    public function hasStock($quantity = 1)
    {
        return $this->stock >= $quantity;
    }

    public function reduceStock($quantity, $specId = 0, $reason = '', int $operatorId = 0, string $operatorName = '')
    {
        $quantity = (int) $quantity;
        $specId = (int) ($specId ?: 0);

        $affected = self::where('id', $this->id)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        if ($affected <= 0) {
            throw new \Exception('库存不足');
        }

        $this->refresh();

        $now = time();
        ItemSpecStockLog::create([
            'item_id' => $this->id,
            'spec_id' => $specId,
            'change_type' => 20,
            'change_quantity' => -$quantity,
            'before_stock' => $this->stock + $quantity,
            'after_stock' => $this->stock,
            'change_reason' => $reason,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
            'app_id' => $this->app_id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this;
    }

    public function addStock($quantity, $specId = 0, $reason = '', int $operatorId = 0, string $operatorName = '')
    {
        $quantity = (int) $quantity;
        $specId = (int) ($specId ?: 0);
        $beforeStock = $this->stock;

        self::where('id', $this->id)
            ->increment('stock', $quantity);

        $this->refresh();

        $now = time();
        ItemSpecStockLog::create([
            'item_id' => $this->id,
            'spec_id' => $specId,
            'change_type' => 10,
            'change_quantity' => $quantity,
            'before_stock' => $beforeStock,
            'after_stock' => $this->stock,
            'change_reason' => $reason,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
            'app_id' => $this->app_id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this;
    }

    public function addSales($quantity)
    {
        // 原子操作：避免并发下 read-modify-write 丢更新
        self::where('id', $this->id)->increment('total_sales', (int) $quantity);
        return $this;
    }

    public function addClick()
    {
        // 原子操作：避免并发下 read-modify-write 丢更新
        self::where('id', $this->id)->increment('click', 1);
        return $this;
    }

    public function supportsCoupon()
    {
        return $this->supports_coupon == 1;
    }

    public function supportsVip()
    {
        return $this->supports_vip == 1;
    }

    public function supportsDiscount()
    {
        return $this->supports_discount == 1;
    }

    public function isVirtual()
    {
        return $this->is_virtual == 1;
    }

    public function isFreeShipping()
    {
        return $this->is_free_shipping == 1;
    }

    public function getPrice($specId = 0)
    {
        if ($specId > 0) {
            $spec = $this->specs()->where('id', $specId)->first();
            return $spec ? $spec->price : $this->getMinPrice();
        }
        return $this->getMinPrice();
    }

    public function getMinPrice()
    {
        $minPrice = $this->specs()->min('price');
        return $minPrice ?: 0;
    }

    public function getMaxPrice()
    {
        $maxPrice = $this->specs()->max('price');
        return $maxPrice ?: 0;
    }

    public function getSpecStock($specId)
    {
        $spec = $this->specs()->where('id', $specId)->first();
        return $spec ? $spec->stock : 0;
    }

    public function hasSpecStock($specId, $quantity = 1)
    {
        return $this->getSpecStock($specId) >= $quantity;
    }

    public function reduceSpecStock($specId, $quantity, $reason = '', int $operatorId = 0, string $operatorName = '')
    {
        $quantity = (int) $quantity;
        $specId = (int) $specId;

        // 原子操作：WHERE store_count >= quantity + decrement，防止并发超卖
        $affected = \app\model\ItemSpecPrice::where('id', $specId)
            ->where('item_id', $this->id)
            ->where('store_count', '>=', $quantity)
            ->decrement('store_count', $quantity);

        if ($affected <= 0) {
            // 二次确认：是规格不存在还是库存不足
            $spec = $this->specs()->where('id', $specId)->first();
            if (!$spec) {
                throw new \Exception('规格不存在');
            }
            throw new \Exception('规格库存不足');
        }

        $spec = $this->specs()->where('id', $specId)->first();
        $spec->refresh();

        $now = time();
        ItemSpecStockLog::create([
            'item_id' => $this->id,
            'spec_id' => $specId,
            'change_type' => 20,
            'change_quantity' => -$quantity,
            'before_stock' => $spec->store_count + $quantity,
            'after_stock' => $spec->store_count,
            'change_reason' => $reason,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
            'app_id' => $this->app_id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this;
    }
}
