<?php

namespace app\model;

use app\model\BaseModel;

/**
 * 商品类型模型
 */
class ItemType extends BaseModel
{
    protected $table = 'yxshop_item_types';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'type_desc',
        'type_icon',
        'sort',
        'status',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    protected static function booted()
    {
        static::creating(function ($model) {
            if (empty($model->created_at)) {
                $model->created_at = time();
            }
            if (empty($model->updated_at)) {
                $model->updated_at = time();
            }
        });
    }

    // 状态常量
    const STATUS_DISABLED = 0;  // 禁用
    const STATUS_ENABLED = 1;   // 启用
    
    /**
     * 关联商品
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'type_id', 'id');
    }
    
    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
    
    /**
     * 检查是否可用
     */
    public function isAvailable()
    {
        return $this->status == self::STATUS_ENABLED;
    }
    
    /**
     * 获取状态文本
     */
    public function getStatusTextAttribute()
    {
        return $this->status == self::STATUS_ENABLED ? '启用' : '禁用';
    }
}