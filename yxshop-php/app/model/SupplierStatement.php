<?php

namespace app\model;

/**
 * 供应商对账单模型
 */
class SupplierStatement extends BaseModel
{
    protected $table = 'yxshop_supplier_statements';

    protected $softDeleteEnabled = false;

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'statement_no', 'supplier_id', 'start_date', 'end_date',
        'total_orders', 'total_sales', 'commission', 'settle_amount',
        'status', 'operator_id', 'remark', 'app_id',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'supplier_id' => 'integer',
        'start_date' => 'integer',
        'end_date' => 'integer',
        'total_orders' => 'integer',
        'total_sales' => 'float',
        'commission' => 'float',
        'settle_amount' => 'float',
        'status' => 'integer',
        'operator_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];
}
