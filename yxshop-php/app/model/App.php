<?php

namespace app\model;

/**
 * 应用模型
 */
class App extends BaseModel
{
    protected $table = 'yxshop_apps';

    protected $fillable = [
        'app_name', 'app_type', 'app_key', 'app_secret',
        'status', 'created_at', 'updated_at', 'deleted_at',
        // 兼容旧字段
        'name', 'app_id', 'secret', 'mch_id', 'api_key', 'ver',
        'logo_id', 'category_style', 'share_title', 'owner_id', 'primary_color', 'secondary_color'
    ];

    protected $hidden = ['secret', 'api_key'];

    protected $casts = [
        'id' => 'integer',
        'logo_id' => 'integer',
        'category_style' => 'integer',
        'owner_id' => 'integer',
        'deleted_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'owner_id', 'id');
    }

    /**
     * 管理员关联
     */
    public function admins()
    {
        return $this->hasMany(Admin::class, 'app_id', 'id');
    }

    /**
     * 应用用户
     */
    public function users()
    {
        return $this->hasMany(User::class, 'app_id', 'id');
    }

    /**
     * 应用订单
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'app_id', 'id');
    }

    /**
     * 应用商品
     */
    public function items()
    {
        return $this->hasMany(Item::class, 'app_id', 'id');
    }
}
