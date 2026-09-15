<?php

namespace app\model;

/**
 * 商品收藏模型
 */
class ItemFavorite extends BaseModel
{
    protected $table = 'yxshop_item_favorites';

    protected $fillable = [
        'user_id', 'item_id', 'app_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'item_id' => 'integer',
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
}
