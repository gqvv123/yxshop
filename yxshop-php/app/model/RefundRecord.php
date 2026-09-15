<?php

namespace app\model;

class RefundRecord extends BaseModel
{
    protected $table = 'yxshop_refund_records';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const STATUS_PENDING = 10;
    const STATUS_PROCESSING = 20;
    const STATUS_SUCCESS = 30;
    const STATUS_FAILED = 40;

    const TYPE_ORIGINAL = 1;
    const TYPE_BALANCE = 2;

    protected $fillable = [
        'refund_no', 'order_id', 'order_no', 'after_sales_id', 'user_id',
        'refund_amount', 'refund_reason', 'refund_type', 'refund_status',
        'transaction_id', 'refund_transaction_id', 'operator_type',
        'operator_id', 'operator_name', 'failed_reason', 'app_id'
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refund_type' => 'integer',
        'refund_status' => 'integer',
        'operator_type' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
        'deleted_at' => 'integer'
    ];

    public function afterSales()
    {
        return $this->belongsTo(AfterSales::class, 'after_sales_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function getStatusText(): string
    {
        return [
            self::STATUS_PENDING => '待处理',
            self::STATUS_PROCESSING => '退款中',
            self::STATUS_SUCCESS => '退款成功',
            self::STATUS_FAILED => '退款失败',
        ][$this->refund_status] ?? '未知';
    }

    public function getTypeText(): string
    {
        return [
            self::TYPE_ORIGINAL => '原路退回',
            self::TYPE_BALANCE => '余额退回',
        ][$this->refund_type] ?? '未知';
    }
}
