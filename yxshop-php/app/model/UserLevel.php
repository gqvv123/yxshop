<?php

namespace app\model;

/**
 * 用户等级模型
 */
class UserLevel extends BaseModel
{
    protected $table = 'yxshop_user_levels';

    protected $fillable = [
        'name', 'level', 'experience', 'discount', 'status', 'agio', 'is_default', 'description', 'app_id'
    ];

    protected $casts = [
        'level' => 'integer',
        'experience' => 'integer',
        'discount' => 'decimal:2',
        'status' => 'integer',
        'agio' => 'decimal:2',
        'is_default' => 'integer',
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
     * 等级用户
     */
    public function users()
    {
        return $this->hasMany(User::class, 'level_id', 'id');
    }
}
