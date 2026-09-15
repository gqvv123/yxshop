<?php

namespace app\model;

/**
 * 商品规格模型
 */
class ItemSpec extends BaseModel
{
    protected $table = 'yxshop_specs';

    protected $fillable = [
        'name',
        'type_id',
        'order',
        'sort_order',
        'search_index',
        'status',
        'app_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'type_id' => 'integer',
        'order' => 'integer',
        'sort_order' => 'integer',
        'search_index' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
        'deleted_at' => 'integer',
    ];

    const STATUS_ENABLED = 1;
    const STATUS_DISABLED = 0;

    /**
     * 关联规格项
     */
    public function specItems()
    {
        return $this->hasMany(ItemSpecItem::class, 'spec_id', 'id');
    }

    /**
     * 关联商品
     */
    public function items()
    {
        return $this->belongsToMany(Item::class, 'yxshop_item_specs', 'spec_id', 'item_id');
    }
}
