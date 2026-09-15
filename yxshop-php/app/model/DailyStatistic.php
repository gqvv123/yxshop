<?php

namespace app\model;

class DailyStatistic extends BaseModel
{
    protected $table = 'yxshop_daily_statistics';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'stat_date', 'new_users', 'order_users', 'order_count',
        'order_amount', 'pay_amount', 'refund_amount', 'page_views', 'app_id'
    ];

    protected $casts = [
        'stat_date' => 'integer',
        'new_users' => 'integer',
        'order_users' => 'integer',
        'order_count' => 'integer',
        'order_amount' => 'decimal:2',
        'pay_amount' => 'decimal:2',
        'refund_amount' => 'decimal:2',
        'page_views' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}