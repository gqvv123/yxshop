<?php

namespace app\model;

/**
 * 系统设置模型
 */
class Setting extends BaseModel
{
    protected $table = 'yxshop_settings';

    public $incrementing = true;

    protected $keyType = 'int';

    protected $fillable = [
        'key', 'value', 'type', 'is_encrypted', 'group', 'description', 'app_id'
    ];

    protected $casts = [
        'is_encrypted' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];
}
