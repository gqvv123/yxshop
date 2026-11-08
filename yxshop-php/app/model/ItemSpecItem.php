<?php

namespace app\model;

/**
 * 商品规格项模型
 */
class ItemSpecItem extends BaseModel
{
    protected $table = 'yxshop_spec_items';

    protected $fillable = [
        'spec_id',
        'item',
        'order',
        'app_id',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'order' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
        'deleted_at' => 'integer',
    ];

    /**
     * 关联规格
     */
    public function spec()
    {
        return $this->belongsTo(ItemSpec::class, 'spec_id', 'id');
    }

    /**
     * 关联规格价格
     */
    public function specPrices()
    {
        return $this->hasMany(ItemSpecPrice::class, 'spec_item_id', 'id');
    }

    /**
     * 关联商品
     */
    public function items()
    {
        return $this->belongsToMany(Item::class, 'yxshop_item_specs', 'spec_item_id', 'item_id');
    }
}
