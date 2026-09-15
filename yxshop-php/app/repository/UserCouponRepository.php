<?php

namespace app\repository;

use app\model\UserCoupon;

/**
 * 用户优惠券仓储类
 */
class UserCouponRepository extends BaseRepository
{
    protected $model = UserCoupon::class;

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $query = $this->query()->with(['coupon', 'user'])->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->paginate($pageSize);
    }

    /**
     * 获取用户优惠券列表
     */
    public function getUserCoupons($userId, $status = null, $appId = 0)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->with(['coupon']);

        if ($status !== null) {
            $query->where('status', $status);
        }
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 按 (user_id, coupon_id, status) 精确查询单条
     * 用于下单/取消时校验优惠券归属与状态
     */
    public function findByUserAndCoupon(int $userId, int $couponId, ?int $status = null): ?UserCoupon
    {
        $q = $this->query()
            ->where('user_id', $userId)
            ->where('coupon_id', $couponId);
        if ($status !== null) {
            $q->where('status', $status);
        }
        return $q->first();
    }

    /**
     * 获取用户可用的优惠券（字段对齐 Coupon 模型：start_at/end_at）
     */
    public function getUserAvailableCoupons($userId, $appId = 0)
    {
        $now = time();
        return $this->query()
            ->where('user_id', $userId)
            ->where('status', UserCoupon::STATUS_UNUSED)
            ->whereHas('coupon', function ($q) use ($now) {
                // Coupon 表字段：固定时间段类型才看 start_at/end_at
                $q->where(function ($q2) use ($now) {
                    $q2->where('expiry_type', '!=', 20)  // 非固定时间不卡时间
                       ->orWhere(function ($q3) use ($now) {
                           $q3->where('expiry_type', 20)
                              ->where('start_at', '<=', $now)
                              ->where('end_at', '>=', $now);
                       });
                });
            })
            ->with(['coupon'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 检查用户是否已有优惠券
     */
    public function hasUserCoupon($userId, $couponId)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->exists();
    }

    /**
     * 用户对某优惠券的已领取次数（用于 user_limit_count 风控）
     */
    public function countByUserAndCoupon(int $userId, int $couponId): int
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->count();
    }

    /**
     * 行锁查未使用用户优惠券（防并发使用）
     */
    public function lockForUpdateUnused(int $userCouponId): ?UserCoupon
    {
        return $this->query()
            ->where('id', $userCouponId)
            ->where('status', UserCoupon::STATUS_UNUSED)
            ->lockForUpdate()
            ->first();
    }

    /**
     * 获取用户未使用优惠券（含 coupon 关联，用于结算预览）
     */
    public function getUnusedWithCouponByUser(int $userId)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('status', UserCoupon::STATUS_UNUSED)
            ->with('coupon')
            ->get();
    }

    /**
     * 统计用户未使用优惠券数量
     */
    public function countUnusedByUser(int $userId): int
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('status', UserCoupon::STATUS_UNUSED)
            ->count();
    }

    /**
     * 用户优惠券状态计数（每项 clone 防 where 累加 bug）
     */
    public function getUserCouponStats($userId, $appId = 0)
    {
        $base = $this->query()->where('user_id', $userId);
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total'   => (clone $base)->count(),
            'unused'  => (clone $base)->where('status', UserCoupon::STATUS_UNUSED)->count(),
            'used'    => (clone $base)->where('status', UserCoupon::STATUS_USED)->count(),
            'expired' => (clone $base)->where('status', UserCoupon::STATUS_EXPIRED)->count(),
        ];
    }

    /**
     * 即将过期的用户优惠券（字段对齐 Coupon 模型 end_at）
     */
    public function getExpiringUserCoupons($userId, $days = 7, $appId = 0)
    {
        $expireTime = time() + ($days * 86400);
        $now = time();
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('status', UserCoupon::STATUS_UNUSED)
            ->whereHas('coupon', function ($q) use ($expireTime, $now) {
                $q->where('expiry_type', 20)
                  ->where('end_at', '<=', $expireTime)
                  ->where('end_at', '>', $now);
            })
            ->with(['coupon']);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }
}
