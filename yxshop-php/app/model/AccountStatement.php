<?php

namespace app\model;

/**
 * 平台对账单模型
 */
class AccountStatement extends BaseModel
{
    protected $table = 'yxshop_account_statements';

    protected $softDeleteEnabled = false;

    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'statement_no', 'statement_type', 'start_date', 'end_date',
        'total_orders', 'total_amount', 'total_refund', 'platform_fee',
        'settle_amount', 'status', 'confirmed_at', 'paid_at',
        'operator_id', 'operator_name', 'remark', 'app_id',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'start_date' => 'integer',
        'end_date' => 'integer',
        'total_orders' => 'integer',
        'total_amount' => 'float',
        'total_refund' => 'float',
        'platform_fee' => 'float',
        'settle_amount' => 'float',
        'status' => 'integer',
        'confirmed_at' => 'integer',
        'paid_at' => 'integer',
        'operator_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function items()
    {
        return $this->hasMany(AccountStatementItem::class, 'statement_id', 'id');
    }
}
