<?php

namespace app\model;

/**
 * 商品搜索记录模型
 *
 * 注意：该表 yxshop_item_searches 使用 auto_increment 主键（非雪花 ID），
 * 且只有 created_at 列、没有 updated_at 列，故需覆盖 BaseModel 的默认设置。
 */
class ItemSearch extends BaseModel
{
    protected $table = 'yxshop_item_searches';

    // 该表使用 auto_increment，非雪花 ID
    public $incrementing = true;
    protected $keyType = 'int';

    // 表只有 created_at，没有 updated_at
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'keyword', 'app_id', 'ip', 'result_count', 'created_at'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'app_id' => 'integer',
        'result_count' => 'integer',
        'created_at' => 'integer',
    ];

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
