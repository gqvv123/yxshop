<?php

namespace app\service;

use app\repository\CouponItemRepository;
use app\exception\BusinessException;
use app\validate\CouponValidate;
use Exception;

/**
 * 优惠券商品服务类
 *
 * @property CouponItemRepository $repository
 */
class CouponItemService extends BaseService
{
    public function __construct(CouponItemRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getCouponList($appId = 0, $status = null)
    {
        try {
            $this->logInfo('获取优惠券列表开始', ['app_id' => $appId, 'status' => $status]);

            $coupons = $this->repository->listCoupons((int) $appId, $status);

            $this->logInfo('获取优惠券列表成功', ['app_id' => $appId]);
            return $coupons;

        } catch (Exception $e) {
            $this->logError('获取优惠券列表失败', [
                'app_id' => $appId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createCoupon(array $data)
    {
        try {
            $this->logInfo('创建优惠券开始', ['data' => $data]);

            $this->validateWith(CouponValidate::class, 'create', $data);

            if ($data['send_start_time'] >= $data['send_end_time']) {
                throw new BusinessException('发放开始时间不能大于等于结束时间');
            }

            if ($data['use_start_time'] >= $data['use_end_time']) {
                throw new BusinessException('使用开始时间不能大于等于结束时间');
            }

            $coupon = $this->repository->create($data);

            $this->logInfo('创建优惠券成功', ['coupon_id' => $coupon->coupon_id]);
            return $coupon;

        } catch (Exception $e) {
            $this->logError('创建优惠券失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateCoupon($id, array $data)
    {
        try {
            $this->logInfo('更新优惠券开始', ['id' => $id, 'data' => $data]);

            $this->validateWith(CouponValidate::class, 'update', $data);

            $coupon = $this->repository->findOrFail($id);

            if (isset($data['send_start_time']) && isset($data['send_end_time'])) {
                if ($data['send_start_time'] >= $data['send_end_time']) {
                    throw new BusinessException('发放开始时间不能大于等于结束时间');
                }
            }

            if (isset($data['use_start_time']) && isset($data['use_end_time'])) {
                if ($data['use_start_time'] >= $data['use_end_time']) {
                    throw new BusinessException('使用开始时间不能大于等于结束时间');
                }
            }

            $coupon = $this->repository->update($id, $data);

            $this->logInfo('更新优惠券成功', ['coupon_id' => $id]);
            return $coupon;

        } catch (Exception $e) {
            $this->logError('更新优惠券失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteCoupon($id)
    {
        try {
            $this->logInfo('删除优惠券开始', ['id' => $id]);

            $coupon = $this->repository->findOrFail($id);

            if ($coupon->send_num > 0) {
                throw new BusinessException('该优惠券已发放，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除优惠券成功', ['coupon_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除优惠券失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function receiveCoupon($couponId, $userId, $appId)
    {
        try {
            $this->logInfo('领取优惠券开始', [
                'coupon_id' => $couponId,
                'user_id' => $userId,
                'app_id' => $appId
            ]);

            $coupon = $this->repository->findOrFail($couponId);

            if (!$coupon->canReceive()) {
                throw new BusinessException('优惠券不可领取');
            }

            if ($this->userCouponRepository->hasUserCoupon($userId, $couponId)) {
                throw new BusinessException('您已领取过该优惠券');
            }

            $userCoupon = $this->userCouponRepository->create([
                'user_id' => $userId,
                'coupon_id' => $couponId,
                'status' => UserCoupon::STATUS_UNUSED,
                'app_id' => $appId,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            $coupon->incrementSendNum();

            $this->logInfo('领取优惠券成功', ['user_coupon_id' => $userCoupon->id]);
            return $userCoupon;

        } catch (Exception $e) {
            $this->logError('领取优惠券失败', [
                'coupon_id' => $couponId,
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getUserCoupons($userId, $status = null, $appId = 0)
    {
        try {
            return $this->userCouponRepository->getUserCoupons($userId, $status, $appId);

        } catch (Exception $e) {
            $this->logError('获取用户优惠券失败', [
                'user_id' => $userId,
                'status' => $status,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getAvailableCoupons($appId = 0, $limit = 0)
    {
        try {
            return $this->repository->getAvailableCoupons($appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取可用优惠券失败', [
                'app_id' => $appId,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getCouponStats($appId = 0)
    {
        try {
            return $this->repository->getCouponStats($appId);

        } catch (Exception $e) {
            $this->logError('获取优惠券统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}