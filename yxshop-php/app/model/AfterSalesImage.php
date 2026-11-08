<?php

namespace app\model;

/**
 * 售后申请图片附件
 *
 * 对应表：yxshop_after_sales_images（原 yxshop_service_images）
 * 外键 after_sales_id 关联 AfterSales
 */
class AfterSalesImage extends BaseModel
{
    protected $table = 'yxshop_after_sales_images';

    protected $fillable = [
        'after_sales_id', 'image_id', 'app_id',
    ];

    protected $casts = [
        'after_sales_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function afterSales()
    {
        return $this->belongsTo(AfterSales::class, 'after_sales_id', 'id');
    }
}
