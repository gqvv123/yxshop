<?php

namespace app\model;

/**
 * 商品规格价格模型
 */
class ItemSpecPrice extends BaseModel
{
    protected $table = 'yxshop_item_spec_prices';

    protected $fillable = [
        'item_id', 'spec_key', 'spec_key_name', 'spec_values', 'price', 'cost_price', 'market_price',
        'store_count', 'weight', 'sku', 'image', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'price' => 'decimal:2',
        'cost_price' => 'decimal:2',
        'market_price' => 'decimal:2',
        'store_count' => 'integer',
        'weight' => 'decimal:2',
        'spec_values' => 'array',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    /**
     * 商品关联
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    /**
     * 应用关联
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 检查库存
     */
    public function hasStock($quantity = 1)
    {
        return $this->store_count >= $quantity;
    }

    /**
     * 减少库存
     */
    public function reduceStock($quantity, $reason = '', int $operatorId = 0, string $operatorName = '')
    {
        $affected = self::where('id', $this->id)
            ->where('store_count', '>=', $quantity)
            ->decrement('store_count', $quantity);

        if ($affected <= 0) {
            throw new \Exception('库存不足');
        }

        $this->refresh();

        $now = time();
        ItemSpecStockLog::create([
            'item_id' => $this->item_id,
            'spec_id' => $this->id,
            'change_type' => 20,
            'change_quantity' => -$quantity,
            'before_stock' => $this->store_count + $quantity,
            'after_stock' => $this->store_count,
            'change_reason' => $reason,
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
            'app_id' => $this->app_id,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this;
    }

    /**
     * 增加库存
     */
    public function addStock($quantity, $reason = '', int $operatorId = 0, string $operatorName = '')
    {
        self::where('id', $this->id)
            ->increment('store_count', $quantity);

        $this->refresh();

        $now = time();
        ItemSpecStockLog::create([
            'item_id' => $this->item_id,
            'spec_id' => $this->id,
            'change_type' => 10,
            'change_quantity' => $quantity,
            'before_stock' => $this->store_count - $quantity,
            'after_stock' => $this->store_count,
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
