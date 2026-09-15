<?php

namespace app\model;

class MonthlyStatistic extends BaseModel
{
    protected $table = 'yxshop_monthly_statistics';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'stat_month', 'new_users', 'total_users', 'order_count',
        'order_amount', 'pay_amount', 'refund_amount', 'avg_order_amount', 'app_id'
    ];

    protected $casts = [
        'stat_month' => 'integer',
        'new_users' => 'integer',
        'total_users' => 'integer',
        'order_count' => 'integer',
        'order_amount' => 'decimal:2',
        'pay_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'avg_order_amount' => 'decimal:2',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}