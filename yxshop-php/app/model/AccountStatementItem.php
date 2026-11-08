<?php

namespace app\model;

/**
 * 平台对账单明细模型
 */
class AccountStatementItem extends BaseModel
{
    protected $table = 'yxshop_account_statement_items';

    protected $softDeleteEnabled = false;

    public $incrementing = true;
    protected $keyType = 'int';

    const UPDATED_AT = null;

    protected $fillable = [
        'statement_id', 'order_id', 'order_no', 'user_id',
        'order_amount', 'refund_amount', 'commission', 'item_type',
        'remark', 'app_id', 'created_at',
    ];

    protected $casts = [
        'id' => 'integer',
        'statement_id' => 'integer',
        'order_id' => 'integer',
        'user_id' => 'integer',
        'order_amount' => 'float',
        'refund_amount' => 'float',
        'commission' => 'float',
        'app_id' => 'integer',
        'created_at' => 'integer',
    ];

    public function statement()
    {
        return $this->belongsTo(AccountStatement::class, 'statement_id', 'id');
    }
}
