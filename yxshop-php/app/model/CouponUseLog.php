<?php

namespace app\model;

class CouponUseLog extends BaseModel
{
    protected $table = 'yxshop_coupon_use_logs';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    /**
     * 该表只有 created_at，没有 updated_at 列
     * 禁用 updated_at 自动管理，避免 SQL 报 Unknown column 'updated_at'
     */
    const UPDATED_AT = null;

    const ACTION_RECEIVE = 'receive';
    const ACTION_USE    = 'use';
    const ACTION_EXPIRE = 'expire';
    const ACTION_CANCEL = 'cancel';

    protected $fillable = [
        'user_coupon_id', 'coupon_id', 'user_id', 'action',
        'order_id', 'discount_amount', 'ip', 'device',
        'remark', 'operator_id', 'operator_name',
    ];

    protected $casts = [
        'user_coupon_id' => 'integer',
        'coupon_id' => 'integer',
        'user_id' => 'integer',
        'order_id' => 'integer',
        'discount_amount' => 'decimal:2',
        'operator_id' => 'integer',
        'created_at' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    public function userCoupon()
    {
        return $this->belongsTo(UserCoupon::class, 'user_coupon_id', 'id');
    }
}
