<?php

namespace app\model;

/**
 * 商品规格库存变动日志模型
 */
class ItemSpecStockLog extends BaseModel
{
    protected $table = 'yxshop_item_spec_stock_logs';

    // 该表为 AUTO_INCREMENT 日志表，禁用 BaseModel 的 Snowflake 主键
    protected $usesSnowflake = false;
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'item_id', 'spec_id', 'change_type', 'change_quantity', 'before_stock',
        'after_stock', 'change_reason', 'operator_id', 'operator_name', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'spec_id' => 'integer',
        'change_type' => 'integer',
        'change_quantity' => 'integer',
        'before_stock' => 'integer',
        'after_stock' => 'integer',
        'operator_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    // 变动类型常量
    const TYPE_IN = 10;          // 入库
    const TYPE_OUT = 20;         // 出库
    const TYPE_ADJUST = 30;      // 调整
    const TYPE_RETURN = 40;      // 退货
    const TYPE_DAMAGE = 50;      // 损耗

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

    /**
     * 获取变动类型文本
     */
    public function getChangeTypeTextAttribute()
    {
        $typeMap = [
            self::TYPE_IN => '入库',
            self::TYPE_OUT => '出库',
            self::TYPE_ADJUST => '调整',
            self::TYPE_RETURN => '退货',
            self::TYPE_DAMAGE => '损耗',
        ];
        return $typeMap[$this->change_type] ?? '未知';
    }
}
