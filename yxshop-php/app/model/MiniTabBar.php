<?php

namespace app\model;

use app\model\BaseModel;

class MiniTabBar extends BaseModel
{
    protected $table = 'yxshop_mini_tab_bars';

    protected $fillable = [
        'color', 'selected_color', 'background_color',
        'border_style', 'items', 'app_id',
    ];

    protected $casts = [
        'items' => 'array',
        'app_id' => 'integer',
        'deleted_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];
}