<?php

namespace app\repository;

use app\model\Coupon;

/**
 * 优惠券仓储类
 *
 * 字段对齐 app\model\Coupon：
 *   status(1=启用), start_at, end_at, total_quantity, used_quantity,
 *   min_amount, discount_amount, discount_rate, expiry_type, expiry_days
 */
class CouponRepository extends BaseRepository
{
    protected $model = Coupon::class;

    /**
     * 获取可领取的优惠券
     */
    public function getAvailableCoupons($appId = 0, $limit = 0)
    {
        $now = time();
        $query = $this->query()
            ->where('status', 1)
            ->where(function ($q) use ($now) {
                // 固定时间段类型需在有效期内
                $q->where('expiry_type', '!=', Coupon::EXPIRY_FIXED_TIME)
                  ->orWhere(function ($q2) use ($now) {
                      $q2->where('expiry_type', Coupon::EXPIRY_FIXED_TIME)
                         ->where('start_at', '<=', $now)
                         ->where('end_at', '>=', $now);
                  });
            })
            ->whereRaw('used_quantity < total_quantity')
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 获取用户可用的优惠券（通过 userCoupons 关联未使用且未过期）
     */
    public function getUserAvailableCoupons($userId, $appId = 0)
    {
        $query = $this->query()
            ->where('status', 1)
            ->whereHas('userCoupons', function ($q) use ($userId) {
                $q->where('user_id', $userId)
                  ->where('status', 0);
            })
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 根据订单金额获取可用优惠券（满足最低消费门槛）
     */
    public function getCouponsForOrder($orderAmount, $appId = 0)
    {
        $query = $this->query()
            ->where('status', 1)
            ->where('min_amount', '<=', $orderAmount)
            ->orderBy('discount_amount', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取优惠券统计
     */
    public function getCouponStats($appId = 0)
    {
        $base = $this->query();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        $now = time();
        // 每项 clone 防止累加 where
        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('status', 1)->count(),
            'expired' => (clone $base)
                ->where('expiry_type', Coupon::EXPIRY_FIXED_TIME)
                ->where('end_at', '<', $now)
                ->count(),
            'used_out' => (clone $base)->whereRaw('used_quantity >= total_quantity')->count(),
        ];
    }

    /**
     * 获取即将过期的优惠券
     */
    public function getExpiringCoupons($days = 7, $appId = 0)
    {
        $expireTime = time() + ($days * 24 * 3600);
        $now = time();
        $query = $this->query()
            ->where('status', 1)
            ->where('expiry_type', Coupon::EXPIRY_FIXED_TIME)
            ->where('end_at', '<=', $expireTime)
            ->where('end_at', '>', $now)
            ->orderBy('end_at', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 后台优惠券列表（按 status 筛选）
     */
    public function getAdminList(int $appId = 0, ?int $status = null)
    {
        $query = $this->query()->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if ($status !== null) {
            $query->where('status', $status);
        }
        return $query->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedByApp(int $appId, int $pageSize = 20)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->orderBy('id', 'desc')
            ->paginate($pageSize);
    }
}
