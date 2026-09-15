<?php

namespace app\model;

use app\exception\BusinessException;

/**
 * 用户优惠券模型
 */
class UserCoupon extends BaseModel
{
    protected $table = 'yxshop_user_coupons';

    protected $fillable = [
        'user_id', 'coupon_id', 'order_id', 'use_time', 'status', 'app_id', 'version'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'coupon_id' => 'integer',
        'order_id' => 'integer',
        'use_time' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'version' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    // 状态常量
    const STATUS_UNUSED = 0;      // 未使用
    const STATUS_USED = 1;        // 已使用
    const STATUS_EXPIRED = 2;     // 已过期

    /**
     * 用户关联
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 优惠券关联
     */
    public function coupon()
    {
        return $this->belongsTo(Coupon::class, 'coupon_id', 'id');
    }

    /**
     * 订单关联
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id', 'id');
    }

    /**
     * 检查是否可使用
     */
    public function canUse()
    {
        return $this->status == self::STATUS_UNUSED && 
               $this->coupon->canUse();
    }

    /**
     * 检查是否过期（委托 Coupon，修正原读取不存在的 use_end_time 字段）
     */
    public function isExpired()
    {
        return $this->coupon ? $this->coupon->isExpired() : false;
    }
}
