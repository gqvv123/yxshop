<?php

namespace app\model;

/**
 * 货币模型
 */
class Currency extends BaseModel
{
    protected $table = 'yxshop_currencies';

    protected $fillable = [
        'name', 'code', 'symbol', 'exchange_rate', 'status', 
        'is_default', 'sort'
    ];

    protected $casts = [
        'exchange_rate' => 'decimal:4',
        'status' => 'integer',
        'is_default' => 'integer',
        'sort' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    // 状态常量
    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;
}

