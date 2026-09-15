<?php

namespace app\repository;

use app\model\RechargeOrder;

/**
 * 充值订单仓储
 */
class RechargeOrderRepository extends BaseRepository
{
    protected $model = RechargeOrder::class;

    /**
     * 按用户获取充值订单
     */
    public function getByUser(int $userId, int $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['package'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * 按套餐获取订单
     */
    public function getByPackage(int $packageId, int $appId = 0)
    {
        $query = $this->query()->where('package_id', $packageId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['user'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * 按支付状态获取订单
     */
    public function getByPayStatus(int $payStatus, int $appId = 0)
    {
        $query = $this->query()->where('pay_status', $payStatus);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['user', 'package'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * 按状态获取订单
     */
    public function getByStatus(int $status, int $appId = 0)
    {
        $query = $this->query()->where('status', $status);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['user', 'package'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (isset($filters['pay_status']) && $filters['pay_status'] !== '') {
            $query->where('pay_status', $filters['pay_status']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['package_id'])) {
            $query->where('package_id', $filters['package_id']);
        }
        if (!empty($filters['keyword'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['keyword']);
            $query->where('order_no', 'like', '%' . $escaped . '%');
        }
        return $query->with(['user', 'package'])->paginate($pageSize);
    }

    /**
     * 获取充值订单统计
     */
    public function getStats(array $conditions = [])
    {
        $appId = $conditions['app_id'] ?? 0;
        $base = $this->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        return [
            'total' => (clone $base)->count(),
            'paid' => (clone $base)->where('pay_status', RechargeOrder::PAY_STATUS_PAID)->count(),
            'unpaid' => (clone $base)->where('pay_status', RechargeOrder::PAY_STATUS_UNPAID)->count(),
            'completed' => (clone $base)->where('status', RechargeOrder::STATUS_COMPLETED)->count(),
        ];
    }

    /**
     * 获取充值金额统计
     */
    public function getAmountStats(int $appId = 0): array
    {
        $base = $this->query()->where('pay_status', RechargeOrder::PAY_STATUS_PAID);
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        return [
            'total_recharge' => (clone $base)->sum('recharge_amount'),
            'total_bonus' => (clone $base)->sum('bonus_amount'),
            'total_pay' => (clone $base)->sum('pay_amount'),
        ];
    }
}