<?php

namespace app\model;

/**
 * 售后处理细节 / 沟通记录
 *
 * 对应表：yxshop_after_sales_details（原 yxshop_service_details）
 * 用途：售后处理过程中买家/卖家的多轮沟通、附加内容、图片证据
 * 外键 after_sales_id 关联 AfterSales
 */
class AfterSalesDetail extends BaseModel
{
    protected $table = 'yxshop_after_sales_details';

    protected $fillable = [
        'after_sales_id', 'user_id', 'order_id', 'order_item_id',
        'type', 'content', 'is_picture', 'sort', 'status', 'app_id',
    ];

    protected $casts = [
        'after_sales_id' => 'integer',
        'user_id' => 'integer',
        'order_id' => 'integer',
        'order_item_id' => 'integer',
        'type' => 'integer',
        'is_picture' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // type: 0 用户 / 1 商家
    const TYPE_USER = 0;
    const TYPE_MERCHANT = 1;

    public function afterSales()
    {
        return $this->belongsTo(AfterSales::class, 'after_sales_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
