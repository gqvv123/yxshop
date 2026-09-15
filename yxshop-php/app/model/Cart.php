<?php

namespace app\model;

/**
 * 购物车模型
 */
class Cart extends BaseModel
{
    protected $table = 'yxshop_carts';

    protected $fillable = [
        'user_id', 'item_id', 'item_name', 'item_image', 'spec_key',
        'spec_key_name', 'price', 'quantity', 'is_selected', 'store_id', 'app_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'item_id' => 'integer',
        'price' => 'decimal:2',
        'quantity' => 'integer',
        'is_selected' => 'integer',
        'store_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 商品关联
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    /**
     * 计算总价
     */
    public function getTotalPriceAttribute()
    {
        return $this->price * $this->quantity;
    }
}
