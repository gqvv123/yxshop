<?php

namespace app\model;

class OrderItem extends BaseModel
{
    protected $table = 'yxshop_order_items';

    protected $fillable = [
        'order_id', 'item_id', 'name', 'image', 'item_no',
        'spec_type', 'spec_sku_id', 'item_sku_id', 'item_attr', 'content',
        'item_price', 'line_price', 'item_weight',
        'total_num', 'total_price', 'total_pay_price',
        'give_integral', 'is_comment', 'is_service',
        'refund_id', 'service_status', 'service_type',
        'user_id', 'app_id'
    ];

    protected $casts = [
        'item_price' => 'decimal:2',
        'line_price' => 'decimal:2',
        'item_weight' => 'integer',
        'total_num' => 'integer',
        'total_price' => 'decimal:2',
        'total_pay_price' => 'decimal:2',
        'give_integral' => 'decimal:2',
        'is_comment' => 'integer',
        'is_service' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function calculateTotalPrice()
    {
        return $this->item_price * $this->total_num;
    }

    public function calculateTotalIntegral()
    {
        return $this->give_integral * $this->total_num;
    }
}