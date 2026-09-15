<?php

namespace app\model;

/**
 * 商品图片模型
 */
class ItemImage extends BaseModel
{
    protected $table = 'yxshop_item_images';

    protected $fillable = [
        'item_id', 'image_id', 'sort', 'is_main', 'url', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'sort' => 'integer',
        'is_main' => 'integer',
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
}
