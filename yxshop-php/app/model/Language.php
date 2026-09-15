<?php

namespace app\model;

/**
 * 语言模型
 */
class Language extends BaseModel
{
    protected $table = 'yxshop_languages';

    protected $fillable = [
        'name', 'code', 'flag', 'status', 'is_default', 'sort'
    ];

    protected $casts = [
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

