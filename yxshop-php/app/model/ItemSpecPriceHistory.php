<?php

namespace app\model;

/**
 * 商品规格价格历史模型
 */
class ItemSpecPriceHistory extends BaseModel
{
    protected $table = 'yxshop_item_spec_price_histories';

    // 该表为 AUTO_INCREMENT 日志表，禁用 BaseModel 的 Snowflake 主键
    protected $usesSnowflake = false;
    public $incrementing = true;
    protected $keyType = 'int';

    // 日志表不使用软删除
    protected $softDeleteEnabled = false;

    protected $fillable = [
        'item_id', 'spec_id', 'old_price', 'new_price', 'change_reason',
        'operator_id', 'operator_name', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'spec_id' => 'integer',
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
        'operator_id' => 'integer',
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
     * 规格关联
     */
    public function spec()
    {
        return $this->belongsTo(ItemSpecPrice::class, 'spec_id', 'id');
    }

    /**
     * 应用关联
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
