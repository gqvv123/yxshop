<?php

namespace app\model;

/**
 * 广告模型
 */
class Advertisement extends BaseModel
{
    protected $table = 'yxshop_advertisements';

    protected $fillable = [
        'title', 'content', 'image', 'link', 'position', 'sort', 'is_show',
        'start_time', 'end_time', 'app_id'
    ];

    protected $casts = [
        'sort' => 'integer',
        'is_show' => 'integer',
        'start_time' => 'integer',
        'end_time' => 'integer',
        'deleted_at' => 'integer',
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

    /**
     * 检查是否有效
     */
    public function isValid()
    {
        $now = time();
        return $this->is_show == 1 && 
               $this->deleted_at == 0 &&
               $this->start_time <= $now && 
               $this->end_time >= $now;
    }

    /**
     * 检查是否过期
     */
    public function isExpired()
    {
        return time() > $this->end_time;
    }
}
