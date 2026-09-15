<?php

namespace app\model;

class Integral extends BaseModel
{
    protected $table = 'yxshop_integral_logs';

    protected $fillable = [
        'user_id', 'type', 'amount', 'before_balance', 'after_balance',
        'note', 'order_id', 'app_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'type' => 'integer',
        'amount' => 'integer',
        'before_balance' => 'integer',
        'after_balance' => 'integer',
        'order_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    const TYPE_EARN = 1;
    const TYPE_SPEND = 2;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
