<?php

namespace app\model;

class OrderLog extends BaseModel
{
    protected $table = 'yxshop_order_logs';
    protected $primaryKey = 'log_id';
    protected $softDeleteEnabled = false;
    public $timestamps = false;
    protected $usesSnowflake = false;
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'order_id', 'order_no', 'change_type', 'change_field', 'old_value',
        'new_value', 'change_reason', 'operator_type', 'operator_id',
        'operator_name', 'ip', 'user_agent', 'app_id', 'created_at',
    ];

    protected $casts = [
        'order_id' => 'integer',
        'change_type' => 'integer',
        'operator_type' => 'integer',
        'operator_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
    ];

    const CHANGE_TYPE_CREATE = 10;
    const CHANGE_TYPE_PAY = 20;
    const CHANGE_TYPE_SHIP = 30;
    const CHANGE_TYPE_RECEIVE = 40;
    const CHANGE_TYPE_CANCEL = 50;
    const CHANGE_TYPE_REFUND = 60;
    const CHANGE_TYPE_UPDATE = 70;
    const CHANGE_TYPE_STATUS = 80;
    const CHANGE_TYPE_CHANGE_PRICE = 90;
    const CHANGE_TYPE_FREE_SHIPPING = 100;
    const CHANGE_TYPE_AUDIT = 110;
    const CHANGE_TYPE_NOTE = 120;

    const OPERATOR_TYPE_USER = 10;
    const OPERATOR_TYPE_ADMIN = 20;
    const OPERATOR_TYPE_SYSTEM = 30;

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    public static function record($orderId, $orderNo, $changeType, $changeField = '', $oldValue = '', $newValue = '', $changeReason = '', $operatorType = self::OPERATOR_TYPE_SYSTEM, $operatorId = 0, $operatorName = '', $ip = '', $userAgent = '', $appId = 0)
    {
        $log = new self();
        $log->order_id = $orderId;
        $log->order_no = $orderNo;
        $log->change_type = $changeType;
        $log->change_field = $changeField;
        $log->old_value = $oldValue;
        $log->new_value = $newValue;
        $log->change_reason = $changeReason;
        $log->operator_type = $operatorType;
        $log->operator_id = $operatorId;
        $log->operator_name = $operatorName;
        $log->ip = $ip;
        $log->user_agent = $userAgent;
        $log->app_id = $appId;
        $log->created_at = time();
        $log->save();

        return $log;
    }
}
