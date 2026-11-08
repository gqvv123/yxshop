<?php

namespace app\model;

/**
 * 商品浏览记录模型
 */
class ItemView extends BaseModel
{
    protected $table = 'yxshop_item_views';

    // 该表无 updated_at 列，禁用更新时间戳
    const UPDATED_AT = null;

    // 该表 id 为自增整型，不使用 Snowflake ID
    protected $usesSnowflake = false;
    public $incrementing = true;
    protected $keyType = 'int';

    // 该表无 deleted_at 列
    protected $softDeleteEnabled = false;

    protected $fillable = [
        'user_id', 'item_id', 'view_count', 'app_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'item_id' => 'integer',
        'view_count' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
    ];

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 商品关联
     */
    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}
