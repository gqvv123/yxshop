<?php

namespace app\repository;

use app\model\Payment;

/**
 * 支付仓储类
 */
class PaymentRepository extends BaseRepository
{
    protected $model = Payment::class;

    /**
     * 根据订单号查找支付记录
     */
    public function findByOrderNo($orderNo)
    {
        return $this->query()
            ->where('order_no', $orderNo)
            ->first();
    }

    /**
     * 根据事务号查找支付记录
     */
    public function findByTransactionId($transactionId)
    {
        return $this->query()
            ->where('transaction_id', $transactionId)
            ->first();
    }

    /**
     * 获取用户支付记录
     */
    public function getUserPayments($userId, $appId = 0, $status = null)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($status !== null) {
            $query->where('payment_status', $status);
        }

        return $query->get();
    }

    /**
     * 获取支付统计
     */
    public function getPaymentStats($startTime = null, $endTime = null, $appId = 0)
    {
        $query = $this->query();

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
            'total_count' => (clone $query)->count(),
            'success_count' => (clone $query)->where('payment_status', Payment::STATUS_SUCCESS)->count(),
            'pending_count' => (clone $query)->where('payment_status', Payment::STATUS_PENDING)->count(),
            'failed_count' => (clone $query)->where('payment_status', Payment::STATUS_FAILED)->count(),
            'total_amount' => (clone $query)->where('payment_status', Payment::STATUS_SUCCESS)->sum('payment_amount'),
        ];
    }

    /**
     * 获取支付方式统计
     */
    public function getPaymentMethodStats($startTime = null, $endTime = null, $appId = 0)
    {
        $query = $this->query()
            ->where('payment_status', Payment::STATUS_SUCCESS);

        if ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }

        if ($endTime) {
            $query->where('created_at', '<=', $endTime);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->selectRaw('payment_method, COUNT(*) as count, SUM(payment_amount) as total_amount')
            ->groupBy('payment_method')
            ->get();
    }

    /**
     * 获取待处理的支付
     */
    public function getPendingPayments($timeout = 1800)
    {
        $timeoutTime = time() - $timeout;

        return $this->query()
            ->where('payment_status', Payment::STATUS_PENDING)
            ->where('created_at', '<', $timeoutTime)
            ->get();
    }

    /**
     * 成功支付总额（可选时间窗口）
     */
    public function sumPaidAmount(int $appId, ?int $startTime = null, ?int $endTime = null): float
    {
        $query = $this->query()
            ->where('app_id', $appId)
            ->where('payment_status', Payment::STATUS_SUCCESS);

        if ($startTime) $query->where('created_at', '>=', $startTime);
        if ($endTime)   $query->where('created_at', '<=', $endTime);

        return (float) $query->sum('payment_amount');
    }

    /**
     * 按订单查成功支付记录（用于发起退款时定位支付单）
     */
    public function findSuccessByOrderId(int $orderId, bool $lockForUpdate = false)
    {
        $query = $this->query()
            ->where('order_id', $orderId)
            ->where('payment_status', Payment::STATUS_SUCCESS);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }
        return $query->first();
    }

    /**
     * 按订单号查单条（用于回调，支持行锁）
     */
    public function findByOrderNoForUpdate(string $orderNo)
    {
        return $this->query()
            ->where('order_no', $orderNo)
            ->lockForUpdate()
            ->first();
    }

    /**
     * 后台分页列表（含 app_id / status / payment_method 筛选）
     */
    public function getPaginatedList(array $filters = [], int $page = 1, int $pageSize = 20)
    {
        $query = $this->query()->orderBy('created_at', 'desc');

        if (!empty($filters['app_id'])) {
            $query->where('app_id', $filters['app_id']);
        }
        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('payment_status', $filters['status']);
        }
        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 后台基础统计
     */
    public function getAdminStats(int $appId = 0): array
    {
        $base = $this->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        return [
            'total'     => (clone $base)->count(),
            'success'   => (clone $base)->where('payment_status', Payment::STATUS_SUCCESS)->count(),
            'pending'   => (clone $base)->where('payment_status', Payment::STATUS_PENDING)->count(),
            'refunding' => (clone $base)->where('payment_status', defined('app\\model\\Payment::STATUS_REFUNDING') ? Payment::STATUS_REFUNDING : 40)->count(),
        ];
    }

    /**
     * 导出查询
     */
    public function exportByDateRange(?string $startDate, ?string $endDate, $status = '')
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if (!empty($startDate)) {
            $query->where('created_at', '>=', strtotime($startDate));
        }
        if (!empty($endDate)) {
            $query->where('created_at', '<=', strtotime($endDate . ' 23:59:59'));
        }
        if ($status !== '' && $status !== null) {
            $query->where('payment_status', $status);
        }
        return $query->get();
    }
}
