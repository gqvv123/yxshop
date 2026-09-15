<?php

namespace app\model;

class RechargeOrder extends BaseModel
{
    protected $table = 'yxshop_recharge_orders';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    const PAY_STATUS_UNPAID = 0;
    const PAY_STATUS_PAID = 1;

    const STATUS_PENDING = 0;
    const STATUS_COMPLETED = 1;
    const STATUS_CANCELLED = 2;

    protected $fillable = [
        'order_no', 'user_id', 'package_id', 'recharge_amount', 'bonus_amount',
        'bonus_points', 'pay_amount', 'pay_method', 'pay_status', 'status', 'app_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'package_id' => 'integer',
        'recharge_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'bonus_points' => 'integer',
        'pay_amount' => 'decimal:2',
        'pay_status' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function package()
    {
        return $this->belongsTo(RechargePackage::class, 'package_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}