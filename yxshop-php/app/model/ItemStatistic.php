<?php

namespace app\model;

class ItemStatistic extends BaseModel
{
    protected $table = 'yxshop_item_statistics';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'item_id', 'stat_date', 'view_count', 'favorite_count', 'cart_count',
        'order_count', 'pay_count', 'pay_amount', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'stat_date' => 'integer',
        'view_count' => 'integer',
        'favorite_count' => 'integer',
        'cart_count' => 'integer',
        'order_count' => 'integer',
        'pay_count' => 'integer',
        'pay_amount' => 'decimal:2',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}