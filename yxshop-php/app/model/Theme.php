<?php

namespace app\model;

/**
 * 前台主题模型
 *
 * 对应 yxshop_themes 表，用于前台配色/字体/自定义 CSS 管理。
 * 注意：此表无 deleted_at 字段，通过软删除字段手动管理。
 */
class Theme extends BaseModel
{
    protected $table = 'yxshop_themes';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'name', 'app_id', 'is_active',
        'primary_color', 'secondary_color', 'font_family', 'custom_css',
        'created_at', 'updated_at', 'deleted_at',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'is_active' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
        'deleted_at' => 'integer',
    ];
}
