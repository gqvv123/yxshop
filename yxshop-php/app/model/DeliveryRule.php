<?php

namespace app\model;

class DeliveryRule extends BaseModel
{
    protected $table = 'yxshop_delivery_rules';

    protected $fillable = [
        'delivery_id', 'region', 'first', 'first_fee', 'additional',
        'additional_fee', 'min_price', 'min_num', 'delivery_type',
        'app_id', 'created_at'
    ];

    protected $casts = [
        'delivery_id' => 'integer',
        'first' => 'double',
        'first_fee' => 'decimal:2',
        'additional' => 'double',
        'additional_fee' => 'decimal:2',
        'min_price' => 'decimal:2',
        'min_num' => 'integer',
        'delivery_type' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer'
    ];

    const DELIVERY_TYPE_QUANTITY = 10;
    const DELIVERY_TYPE_AMOUNT = 20;
    const DELIVERY_TYPE_BOTH = 30;

    public function getIsDefaultAttribute()
    {
        return $this->region === '' || $this->region === '0' || $this->region === '[]';
    }

    public function getRegionArrayAttribute()
    {
        if ($this->is_default) return [];
        $decoded = json_decode($this->region, true);
        return is_array($decoded) ? $decoded : [];
    }

    public function delivery()
    {
        return $this->belongsTo(Delivery::class, 'delivery_id', 'id');
    }
}
