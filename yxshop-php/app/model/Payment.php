<?php

namespace app\model;

/**
 * 支付模型
 *
 * 数据库字段为 payment_amount / payment_status，
 * 通过 accessor/mutator 别名保持代码中 ->amount / ->status 的兼容写法。
 */
class Payment extends BaseModel
{
    protected $table = 'yxshop_payments';

    protected $fillable = [
        'order_id', 'order_no', 'user_id', 'payment_method', 'payment_amount',
        'payment_status', 'transaction_id', 'refund_reason', 'paid_at', 'app_id'
    ];

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'payment_amount' => 'decimal:2',
        'payment_status' => 'integer',
        'paid_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    // 支付状态常量
    const STATUS_PENDING = 10;    // 待支付
    const STATUS_SUCCESS = 20;    // 支付成功
    const STATUS_FAILED = 30;     // 支付失败
    const STATUS_CANCELLED = 40;  // 支付取消
    const STATUS_REFUNDED = 50;   // 已退款
    const STATUS_REFUNDING = 60;  // 退款中

    // 支付方式常量
    const METHOD_WECHAT = 'wechat';   // 微信支付
    const METHOD_ALIPAY = 'alipay';   // 支付宝
    const METHOD_BALANCE = 'balance'; // 余额支付

    // ---- 别名 accessor/mutator：让代码中 $payment->amount / $payment->status 无缝工作 ----

    /** amount 别名 → payment_amount */
    public function getAmountAttribute()
    {
        return $this->getAttribute('payment_amount');
    }

    public function setAmountAttribute($value)
    {
        $this->attributes['payment_amount'] = $value;
    }

    /** status 别名 → payment_status */
    public function getStatusAttribute()
    {
        return $this->getAttribute('payment_status');
    }

    public function setStatusAttribute($value)
    {
        $this->attributes['payment_status'] = $value;
    }

    /**
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 获取支付状态文本
     */
    public function getStatusTextAttribute()
    {
        $statusMap = [
            self::STATUS_PENDING => '待支付',
            self::STATUS_SUCCESS => '支付成功',
            self::STATUS_FAILED => '支付失败',
            self::STATUS_CANCELLED => '支付取消',
            self::STATUS_REFUNDED => '已退款',
            self::STATUS_REFUNDING => '退款中',
        ];
        return $statusMap[$this->payment_status] ?? '未知状态';
    }

    /**
     * 获取支付方式文本
     */
    public function getMethodTextAttribute()
    {
        $methodMap = [
            self::METHOD_WECHAT => '微信支付',
            self::METHOD_ALIPAY => '支付宝',
            self::METHOD_BALANCE => '余额支付',
        ];
        return $methodMap[$this->payment_method] ?? '未知方式';
    }

    /**
     * 检查是否支付成功
     */
    public function isSuccess()
    {
        return $this->payment_status == self::STATUS_SUCCESS;
    }

    /**
     * 检查是否待支付
     */
    public function isPending()
    {
        return $this->payment_status == self::STATUS_PENDING;
    }

    /**
     * 检查是否已退款
     */
    public function isRefunded()
    {
        return $this->payment_status == self::STATUS_REFUNDED;
    }
}
