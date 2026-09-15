<?php

namespace app\model;

class UserMoneyLog extends BaseModel
{
    protected $table = 'yxshop_user_money_logs';

    protected $fillable = [
        'user_id', 'app_id', 'money', 'before_money', 'after_money',
        'note', 'type', 'order_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'app_id' => 'integer',
        'money' => 'decimal:2',
        'before_money' => 'decimal:2',
        'after_money' => 'decimal:2',
        'type' => 'integer',
        'order_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    const TYPE_INCOME = 1;
    const TYPE_EXPENSE = 2;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
