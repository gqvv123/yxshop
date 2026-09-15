<?php

namespace app\model;

/**
 * 应用导航栏模型
 */
class AppNavbar extends BaseModel
{
    protected $table = 'yxshop_app_navbars';

    protected $fillable = [
        'app_title', 'top_text_color', 'top_background_color', 'app_id'
    ];

    protected $casts = [
        'top_text_color' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    /**
     * 应用关联
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
