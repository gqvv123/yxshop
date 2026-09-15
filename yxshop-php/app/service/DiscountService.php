<?php

namespace app\service;

use app\repository\DiscountRepository;
use app\exception\BusinessException;
use Exception;

class DiscountService extends BaseService
{
    public function __construct(DiscountRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getDiscountList($appId = 0, $page = 1, $pageSize = 20)
    {
        try {
            $list = $this->repository->getPaginatedByApp((int) $appId, (int) $page, (int) $pageSize);
            $this->logInfo('获取优惠活动列表成功', ['app_id' => $appId]);
            return $list;
        } catch (Exception $e) {
            $this->logError('获取优惠活动列表失败', [
                'app_id' => $appId, 'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function createDiscount(array $data)
    {
        try {
            $this->logInfo('创建优惠活动开始', ['data' => $data]);


            if ($data['start_time'] >= $data['end_time']) {
                throw new BusinessException('开始时间不能大于等于结束时间');
            }

            $discount = $this->repository->create($data);

            $this->logInfo('创建优惠活动成功', ['discount_id' => $discount->id]);
            return $discount;

        } catch (Exception $e) {
            $this->logError('创建优惠活动失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateDiscount($id, array $data)
    {
        try {
            $this->logInfo('更新优惠活动开始', ['id' => $id, 'data' => $data]);


            $this->repository->findOrFail($id);

            if (isset($data['start_time']) && isset($data['end_time'])) {
                if ($data['start_time'] >= $data['end_time']) {
                    throw new BusinessException('开始时间不能大于等于结束时间');
                }
            }

            $discount = $this->repository->update($id, $data);

            $this->logInfo('更新优惠活动成功', ['discount_id' => $id]);
            return $discount;

        } catch (Exception $e) {
            $this->logError('更新优惠活动失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function deleteDiscount($id)
    {
        try {
            $this->logInfo('删除优惠活动开始', ['id' => $id]);

            $this->repository->findOrFail($id);
            $this->repository->delete($id);

            $this->logInfo('删除优惠活动成功', ['discount_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除优惠活动失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function calculateDiscount($discountId, $amount)
    {
        try {
            $this->logInfo('计算优惠金额开始', ['discount_id' => $discountId, 'amount' => $amount]);

            $discount = $this->repository->findOrFail($discountId);

            $now = time();
            if ($discount->start_time > $now || $discount->end_time < $now) {
                throw new BusinessException('优惠活动不在有效期内');
            }

            $discountAmount = 0;
            if ($discount->type == 1) {
                $discountAmount = round($amount * $discount->value / 100, 2);
            } elseif ($discount->type == 2) {
                $discountAmount = min($discount->value, $amount);
            }

            $finalAmount = max($amount - $discountAmount, 0);

            $result = [
                'original_amount' => $amount,
                'discount_amount' => $discountAmount,
                'final_amount' => $finalAmount,
            ];

            $this->logInfo('计算优惠金额成功', $result);
            return $result;

        } catch (Exception $e) {
            $this->logError('计算优惠金额失败', [
                'discount_id' => $discountId,
                'amount' => $amount,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getActiveDiscounts($appId)
    {
        try {
            $discounts = $this->repository->getActiveDiscounts((int) $appId);
            $this->logInfo('获取有效优惠活动成功', ['app_id' => $appId, 'count' => $discounts->count()]);
            return $discounts;
        } catch (Exception $e) {
            $this->logError('获取有效优惠活动失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
