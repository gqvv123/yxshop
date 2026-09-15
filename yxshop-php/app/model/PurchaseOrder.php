<?php

namespace app\model;

/**
 * 采购订单模型
 */
class PurchaseOrder extends BaseModel
{
    protected $table = 'yxshop_purchase_orders';

    protected $softDeleteEnabled = false;

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'purchase_no', 'supplier_id', 'warehouse_id', 'total_amount',
        'status', 'auditor_id', 'audited_at', 'remark', 'app_id',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'supplier_id' => 'integer',
        'warehouse_id' => 'integer',
        'total_amount' => 'float',
        'status' => 'integer',
        'auditor_id' => 'integer',
        'audited_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseOrderItem::class, 'purchase_order_id', 'id');
    }
}
