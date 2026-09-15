<?php

namespace app\model;

use app\model\BaseModel;

class MiniPageTemplate extends BaseModel
{
    protected $table = 'yxshop_mini_page_templates';

    protected $fillable = [
        'template_name', 'template_data', 'thumbnail', 'app_id',
    ];

    protected $casts = [
        'template_data' => 'array',
        'app_id' => 'integer',
        'deleted_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];
}