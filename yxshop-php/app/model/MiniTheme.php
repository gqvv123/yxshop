<?php

namespace app\model;

use app\model\BaseModel;

class MiniTheme extends BaseModel
{
    protected $table = 'yxshop_mini_themes';

    protected $fillable = [
        'theme_name', 'primary_color', 'secondary_color',
        'nav_background_color', 'nav_text_color', 'is_active', 'app_id',
    ];

    protected $casts = [
        'is_active' => 'integer',
        'app_id' => 'integer',
        'deleted_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function pages()
    {
        return $this->hasMany(MiniPage::class, 'theme_id', 'id');
    }
}