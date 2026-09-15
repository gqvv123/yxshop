<?php

namespace app\model;

/**
 * 应用预支付ID模型
 */
class AppPrepayId extends BaseModel
{
    protected $table = 'yxshop_app_prepay_ids';

    protected $fillable = [
        'user_id', 'order_id', 'prepay_id', 'can_use_times', 'used_times',
        'pay_status', 'expiry_time', 'app_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'order_id' => 'integer',
        'can_use_times' => 'integer',
        'used_times' => 'integer',
        'pay_status' => 'integer',
        'expiry_time' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 应用关联
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 检查是否可用
     */
    public function isUsable()
    {
        return $this->used_times < $this->can_use_times && 
               time() < $this->expiry_time &&
               $this->pay_status == 0;
    }

    /**
     * 使用预支付ID
     */
    public function use()
    {
        $this->used_times += 1;
        $this->save();
    }
}
