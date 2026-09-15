<?php

namespace app\model;

/**
 * 商品规格销量统计模型
 */
class ItemSpecSales extends BaseModel
{
    protected $table = 'yxshop_item_spec_sales';

    protected $fillable = [
        'item_id', 'spec_id', 'sales_count', 'sales_amount', 'date', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'spec_id' => 'integer',
        'sales_count' => 'integer',
        'sales_amount' => 'decimal:2',
        'date' => 'date',
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
     * 规格关联
     */
    public function spec()
    {
        return $this->belongsTo(ItemSpecPrice::class, 'spec_id', 'id');
    }

    /**
     * 应用关联
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
