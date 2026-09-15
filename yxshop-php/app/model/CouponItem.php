<?php

namespace app\model;

class CouponItem extends BaseModel
{
    protected $table = 'yxshop_coupon_items';

    protected $fillable = [
        'coupon_id', 'item_id', 'app_id'
    ];

    protected $casts = [
        'coupon_id' => 'integer',
        'item_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}