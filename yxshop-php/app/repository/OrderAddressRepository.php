<?php

namespace app\repository;

use app\model\OrderAddress;

/**
 * 订单仓储类
 */
class OrderAddressRepository extends BaseRepository
{
    protected $model = OrderAddress::class;

    /**
     * 根据订单号查找订单
     */
    public function findByOrderNo($orderNo)
    {
        return $this->query()->where('order_no', $orderNo)->first();
    }

    /**
     * 获取用户订单统计
     */
    public function getUserOrderStats($userId, $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);
        
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'pending' => $query->where('order_status', Order::ORDER_STATUS_PENDING)->count(),
            'completed' => $query->where('order_status', Order::ORDER_STATUS_COMPLETE)->count(),
            'cancelled' => $query->where('order_status', Order::ORDER_STATUS_CANCEL)->count(),
        ];
    }

    /**
     * 获取订单销售统计
     */
    public function getSalesStats($startTime = null, $endTime = null, $appId = 0)
    {
        $query = $this->query()
            ->where('order_status', Order::ORDER_STATUS_COMPLETE)
            ->where('pay_status', Order::PAY_STATUS_PAID);

        if ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }

        if ($endTime) {
            $query->where('created_at', '<=', $endTime);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'order_count' => $query->count(),
            'total_amount' => $query->sum('pay_price'),
            'avg_amount' => $query->avg('pay_price'),
        ];
    }

    /**
     * 获取待处理订单
     */
    public function getPendingOrders($appId = 0)
    {
        $query = $this->query()
            ->where('order_status', Order::ORDER_STATUS_PENDING)
            ->with(['user', 'items']);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 获取超时未支付订单
     */
    public function getTimeoutOrders($timeout = 1800) // 30分钟
    {
        $timeoutTime = time() - $timeout;
        
        return $this->query()
            ->where('order_status', Order::ORDER_STATUS_PENDING)
            ->where('pay_status', Order::PAY_STATUS_UNPAID)
            ->where('created_at', '<', $timeoutTime)
            ->get();
    }
}
