<?php

namespace app\model;

/**
 * 商品标签关联模型
 */
class ItemTagRelation extends BaseModel
{
    protected $table = 'yxshop_item_tag_relations';

    protected $fillable = [
        'item_id', 'tag_id', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'tag_id' => 'integer',
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
     * 标签关联
     */
    public function tag()
    {
        return $this->belongsTo(ItemTag::class, 'tag_id', 'id');
    }

    /**
     * 应用关联
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
