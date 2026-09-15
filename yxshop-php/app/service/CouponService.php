<?php

namespace app\service;

use app\repository\CouponRepository;
use app\repository\UserCouponRepository;
use app\repository\CouponUseLogRepository;
use app\model\Coupon;
use app\model\UserCoupon;
use app\model\CouponUseLog;
use app\exception\BusinessException;
use app\validate\CouponValidate;
use Exception;

/**
 * 优惠券服务
 *
 * @property CouponRepository $repository
 */
class CouponService extends BaseService
{
    private UserCouponRepository $userCouponRepository;
    private CouponUseLogRepository $couponUseLogRepository;

    public function __construct(?CouponRepository $repository = null)
    {
        parent::__construct($repository ?? new CouponRepository());
        $this->userCouponRepository = new UserCouponRepository();
        $this->couponUseLogRepository = new CouponUseLogRepository();
    }

    /**
     * 获取优惠券列表（后台）
     */
    public function getCouponList($appId = 0, $status = null)
    {
        try {
            return $this->repository->getAdminList((int) $appId, $status === null ? null : (int) $status);
        } catch (Exception $e) {
            $this->logError('获取优惠券列表失败', compact('appId', 'status') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 创建优惠券
     */
    public function createCoupon(array $data)
    {
        try {
            $this->logInfo('创建优惠券开始', ['data' => $data]);
            $this->validateWith(CouponValidate::class, 'create', $data);
            $this->validateExpiryDates($data);
            $coupon = $this->repository->create($data);
            $this->logInfo('创建优惠券成功', ['coupon_id' => $coupon->id]);
            return $coupon;
        } catch (Exception $e) {
            $this->logError('创建优惠券失败', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新优惠券
     */
    public function updateCoupon($id, array $data)
    {
        try {
            $this->logInfo('更新优惠券开始', ['id' => $id, 'data' => $data]);
            $this->validateWith(CouponValidate::class, 'update', $data);
            $this->repository->findOrFail($id);
            $this->validateExpiryDates($data);
            $coupon = $this->repository->update($id, $data);
            $this->logInfo('更新优惠券成功', ['coupon_id' => $id]);
            return $coupon;
        } catch (Exception $e) {
            $this->logError('更新优惠券失败', ['id' => $id, 'data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除优惠券（已被领取过的不可删除）
     */
    public function deleteCoupon($id)
    {
        try {
            $coupon = $this->repository->findOrFail($id);
            if ((int) ($coupon->used_quantity ?? 0) > 0) {
                throw new BusinessException('该优惠券已被领取，无法删除');
            }
            $this->repository->delete($id);
            $this->logInfo('删除优惠券成功', ['coupon_id' => $id]);
            return true;
        } catch (Exception $e) {
            $this->logError('删除优惠券失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 领取优惠券（风控：检查 user_limit_count 每人限制次数）
     */
    public function receiveCoupon($couponId, $userId, $appId, ?string $ip = null, ?string $device = null)
    {
        try {
            $this->logInfo('领取优惠券开始', compact('couponId', 'userId', 'appId'));

            $coupon = $this->repository->findOrFail($couponId);
            if (!$coupon->canReceive()) {
                throw new BusinessException('优惠券不可领取');
            }

            // === 风控：每人最大领取次数 ===
            $userLimitCount = $coupon->user_limit_count ?? 1;
            if ($userLimitCount > 0) {
                $userReceivedCount = $this->userCouponRepository->countByUserAndCoupon($userId, $couponId);
                if ($userReceivedCount >= $userLimitCount) {
                    throw new BusinessException("该优惠券每人最多领取 {$userLimitCount} 次，您已达到上限");
                }
            }

            return $this->transaction(function () use ($coupon, $userId, $couponId, $appId, $ip, $device) {
                // 原子扣减库存
                if (!$coupon->incrementUsedQuantity()) {
                    throw new BusinessException('优惠券已领完');
                }

                $now = time();
                $userCoupon = $this->userCouponRepository->create([
                    'user_id'    => $userId,
                    'coupon_id'  => $couponId,
                    'status'     => UserCoupon::STATUS_UNUSED,
                    'app_id'     => $appId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                // 写领取日志
                $this->writeUseLog($userCoupon->id, $couponId, $userId, CouponUseLog::ACTION_RECEIVE, null, null, $ip, $device);

                $this->logInfo('领取优惠券成功', ['user_coupon_id' => $userCoupon->id]);
                return $userCoupon;
            });
        } catch (Exception $e) {
            $this->logError('领取优惠券失败', compact('couponId', 'userId', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 使用优惠券（核销）
     */
    public function useCoupon($userCouponId, $orderId, $discountAmount, ?string $ip = null, ?string $device = null)
    {
        try {
            $userCoupon = $this->userCouponRepository->findOrFail($userCouponId);
            $coupon = $this->repository->findOrFail($userCoupon->coupon_id);

            return $this->transaction(function () use ($userCoupon, $coupon, $orderId, $discountAmount, $ip, $device) {
                // 行锁防重复使用
                $locked = $this->userCouponRepository->lockForUpdateUnused($userCoupon->id);
                if (!$locked) {
                    throw new BusinessException('优惠券已使用或不可用', 4002);
                }

                $userCoupon->status = UserCoupon::STATUS_USED;
                $userCoupon->order_id = $orderId;
                $userCoupon->use_time = time();
                $userCoupon->save();

                // 原子增加已用数量
                $coupon->incrementUsedQuantity();

                // 写核销日志
                $this->writeUseLog(
                    $userCoupon->id, $coupon->id, $userCoupon->user_id,
                    CouponUseLog::ACTION_USE, $orderId, $discountAmount, $ip, $device
                );

                $this->logInfo('优惠券核销成功', ['user_coupon_id' => $userCoupon->id, 'order_id' => $orderId]);
                return $userCoupon;
            });
        } catch (Exception $e) {
            $this->logError('优惠券核销失败', ['user_coupon_id' => $userCouponId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取用户优惠券
     */
    public function getUserCoupons($userId, $status = null, $appId = 0)
    {
        try {
            return $this->userCouponRepository->getUserCoupons($userId, $status, $appId);
        } catch (Exception $e) {
            $this->logError('获取用户优惠券失败', compact('userId', 'status', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取可领取优惠券
     */
    public function getAvailableCoupons($appId = 0, $limit = 0)
    {
        try {
            return $this->repository->getAvailableCoupons($appId, $limit);
        } catch (Exception $e) {
            $this->logError('获取可用优惠券失败', compact('appId', 'limit') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 优惠券统计
     */
    public function getCouponStats($appId = 0)
    {
        try {
            return $this->repository->getCouponStats($appId);
        } catch (Exception $e) {
            $this->logError('获取优惠券统计失败', ['app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getPaginatedList($appId, $pageSize = 20)
    {
        return $this->repository->getPaginatedByApp((int) $appId, (int) $pageSize);
    }

    /**
     * 获取优惠券列表（基础，含ID筛选）
     */
    public function getList(int $appId = 0, $ids = null)
    {
        $query = $this->repository->query()->where('status', 1);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if ($ids) {
            $idArr = is_array($ids) ? $ids : explode(',', $ids);
            $query->whereIn('id', $idArr);
        }
        return $query->orderBy('sort', 'desc')->orderBy('created_at', 'desc')->get();
    }

    /**
     * 获取用户优惠券统计
     */
    public function getUserCouponStats(int $userId)
    {
        return $this->userCouponRepository->getUserCouponStats($userId);
    }

    /**
     * 获取用户优惠券列表（分页，按状态）
     */
    public function getMyCouponsPaginated(int $userId, string $status = 'unused', int $page = 1, int $pageSize = 20)
    {
        $query = $this->userCouponRepository->query()
            ->where('user_id', $userId)
            ->with('coupon');

        switch ($status) {
            case 'used':
                $query->where('status', UserCoupon::STATUS_USED);
                break;
            case 'expired':
                $query->where('status', UserCoupon::STATUS_EXPIRED);
                break;
            default:
                $query->where('status', UserCoupon::STATUS_UNUSED);
                break;
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 统计用户领取的优惠券数量
     */
    public function countUserReceived(int $userId, int $couponId): int
    {
        return $this->userCouponRepository->countByUserAndCoupon($userId, $couponId);
    }

    // ============================================================
    // 私有方法
    // ============================================================

    private function validateExpiryDates(array $data): void
    {
        $type = (int) ($data['expiry_type'] ?? 0);
        if ($type === Coupon::EXPIRY_FIXED_TIME) {
            $start = (int) ($data['start_at'] ?? 0);
            $end   = (int) ($data['end_at'] ?? 0);
            if ($start && $end && $start >= $end) {
                throw new BusinessException('有效期开始时间必须小于结束时间');
            }
        }
        if ($type === Coupon::EXPIRY_AFTER_RECEIVE) {
            $days = (int) ($data['expiry_days'] ?? 0);
            if ($days <= 0) {
                throw new BusinessException('领取后生效类型需指定 expiry_days');
            }
        }
    }

    private function writeUseLog(
        int $userCouponId, int $couponId, int $userId, string $action,
        ?int $orderId = null, ?float $discountAmount = null,
        ?string $ip = null, ?string $device = null
    ): void {
        $this->couponUseLogRepository->create([
            'user_coupon_id' => $userCouponId,
            'coupon_id'     => $couponId,
            'user_id'       => $userId,
            'action'        => $action,
            'order_id'      => $orderId,
            'discount_amount' => $discountAmount,
            'ip'            => $ip,
            'device'        => $device,
            'created_at'    => time(),
        ]);
    }
}
