<?php

namespace app\model;

class RechargePackage extends BaseModel
{
    protected $table = 'yxshop_recharge_packages';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $fillable = [
        'package_name', 'recharge_amount', 'bonus_amount', 'bonus_points',
        'sort', 'status', 'app_id'
    ];

    protected $casts = [
        'recharge_amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'bonus_points' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function rechargeOrders()
    {
        return $this->hasMany(RechargeOrder::class, 'package_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}