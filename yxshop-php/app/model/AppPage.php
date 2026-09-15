<?php

namespace app\model;

/**
 * 应用页面模型
 */
class AppPage extends BaseModel
{
    protected $table = 'yxshop_app_pages';

    protected $fillable = [
        'page_type', 'page_name', 'page_data', 'app_id'
    ];

    protected $casts = [
        'page_type' => 'integer',
        'page_data' => 'array',
        'deleted_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    // 页面类型常量
    const TYPE_HOME = 10;        // 首页
    const TYPE_CUSTOM = 20;      // 自定义页

    /**
     * 应用关联
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 获取页面类型文本
     */
    public function getPageTypeTextAttribute()
    {
        $typeMap = [
            self::TYPE_HOME => '首页',
            self::TYPE_CUSTOM => '自定义页',
        ];
        return $typeMap[$this->page_type] ?? '未知';
    }
}
