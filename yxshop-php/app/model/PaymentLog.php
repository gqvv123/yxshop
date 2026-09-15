<?php

namespace app\model;

class PaymentLog extends BaseModel
{
    protected $table = 'yxshop_payment_logs';

    protected $softDeleteEnabled = false;
    public $timestamps = false;

    protected $fillable = [
        'order_id', 'order_no', 'user_id', 'payment_type', 'payment_method',
        'payment_amount', 'payment_status', 'transaction_id', 'prepay_id',
        'payment_time', 'callback_data', 'error_message', 'ip', 'app_id',
        'created_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'user_id' => 'integer',
        'payment_type' => 'integer',
        'payment_amount' => 'decimal:2',
        'payment_status' => 'integer',
        'payment_time' => 'integer',
        'callback_data' => 'array',
        'app_id' => 'integer',
        'created_at' => 'integer',
    ];

    const TYPE_WECHAT = 10;
    const TYPE_ALIPAY = 20;
    const TYPE_BALANCE = 30;

    const STATUS_PENDING = 10;
    const STATUS_PAYING = 20;
    const STATUS_SUCCESS = 30;
    const STATUS_FAILED = 40;
    const STATUS_REFUNDED = 50;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public static function record($orderId, $orderNo, $userId, $paymentType, $paymentMethod, $paymentAmount, $paymentStatus = self::STATUS_PENDING, $transactionId = '', $prepayId = '', $paymentTime = 0, $callbackData = [], $errorMessage = '', $ip = '', $appId = 0)
    {
        $log = new self();
        $log->order_id = $orderId;
        $log->order_no = $orderNo;
        $log->user_id = $userId;
        $log->payment_type = $paymentType;
        $log->payment_method = $paymentMethod;
        $log->payment_amount = $paymentAmount;
        $log->payment_status = $paymentStatus;
        $log->transaction_id = $transactionId;
        $log->prepay_id = $prepayId;
        $log->payment_time = $paymentTime;
        $log->callback_data = $callbackData;
        $log->error_message = $errorMessage;
        $log->ip = $ip;
        $log->app_id = $appId;
        $log->created_at = time();
        $log->save();

        return $log;
    }
}
