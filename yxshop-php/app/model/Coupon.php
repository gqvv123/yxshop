<?php

namespace app\model;

class Coupon extends BaseModel
{
    protected $table = 'yxshop_coupons';

    protected $fillable = [
        'name', 'color', 'type', 'discount_amount', 'discount_rate', 'min_amount',
        'expiry_type', 'expiry_days', 'start_at', 'end_at', 'scope',
        'total_quantity', 'used_quantity', 'sort', 'deleted_at', 'status',
        'app_id', 'min_quantity', 'member_level_id', 'user_limit_type',
        'user_level_limit', 'item_limit_type', 'item_ids',
        'user_limit_count', 'version',
    ];

    protected $softDeleteEnabled = true;

    protected $casts = [
        'color' => 'integer',
        'type' => 'integer',
        'discount_amount' => 'decimal:2',
        'discount_rate' => 'integer',
        'min_amount' => 'decimal:2',
        'expiry_type' => 'integer',
        'expiry_days' => 'integer',
        'total_quantity' => 'integer',
        'used_quantity' => 'integer',
        'sort' => 'integer',
        'deleted_at' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'min_quantity' => 'integer',
        'member_level_id' => 'integer',
        'user_limit_type' => 'integer',
        'user_level_limit' => 'integer',
        'item_limit_type' => 'integer',
        'user_limit_count' => 'integer',
        'version' => 'integer',
        'start_at' => 'integer',
        'end_at' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    const TYPE_MONEY = 1;
    const TYPE_DISCOUNT = 2;
    const TYPE_GIFT = 3;

    const COLOR_BLUE = 10;
    const COLOR_RED = 20;
    const COLOR_PURPLE = 30;
    const COLOR_YELLOW = 40;

    const EXPIRY_AFTER_RECEIVE = 10;
    const EXPIRY_FIXED_TIME = 20;

    const SCOPE_ALL = 10;
    const SCOPE_SPECIFIED = 20;

    public function couponItems()
    {
        return $this->hasMany(CouponItem::class, 'coupon_id', 'id');
    }

    public function couponGoods()
    {
        return $this->couponItems();
    }

    public function userCoupons()
    {
        return $this->hasMany(UserCoupon::class, 'coupon_id', 'id');
    }

    public function canReceive()
    {
        $now = time();
        if ($this->status != 1 || $this->deleted_at > 0) {
            return false;
        }
        if ($this->expiry_type == self::EXPIRY_FIXED_TIME) {
            if ($this->start_at > $now || $this->end_at < $now) {
                return false;
            }
        }
        if ($this->total_quantity > 0 && $this->used_quantity >= $this->total_quantity) {
            return false;
        }
        return true;
    }

    public function canUse()
    {
        $now = time();
        if ($this->expiry_type == self::EXPIRY_FIXED_TIME) {
            return $this->start_at <= $now && $this->end_at >= $now;
        }
        return true;
    }

    public function isExpired()
    {
        if ($this->expiry_type == self::EXPIRY_FIXED_TIME) {
            return time() > $this->end_at;
        }
        return false;
    }

    public function calculateDiscount($orderAmount)
    {
        if ($orderAmount < $this->min_amount) {
            return 0;
        }

        switch ($this->type) {
            case self::TYPE_MONEY:
                $discount = $this->discount_amount;
                return min($discount, $orderAmount);

            case self::TYPE_DISCOUNT:
                $discount = $orderAmount * (1 - $this->discount_rate / 100);
                return round($discount, 2);

            case self::TYPE_GIFT:
                return 0;

            default:
                return 0;
        }
    }

    /**
     * 原子增加已用数量，并带库存守卫防并发超发
     *
     * @return bool 是否成功（库存不足返回 false）
     */
    public function incrementUsedQuantity(): bool
    {
        $query = self::where('id', $this->id);

        // 若设置了总量上限，加 WHERE used_quantity < total_quantity 守卫
        if ($this->total_quantity > 0) {
            $query->where('used_quantity', '<', $this->total_quantity);
        }

        $affected = $query->increment('used_quantity', 1);
        if ($affected === 1) {
            $this->used_quantity += 1;
            return true;
        }
        return false;
    }
}
