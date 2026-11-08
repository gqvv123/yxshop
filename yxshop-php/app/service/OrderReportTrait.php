<?php

namespace app\service;
use app\model\Order;
use Exception;

/**
 * OrderReportTrait：从 OrderService.php 抽离的getOrderStatusCounts、getTrackingOrders、getCount、getTodayOrderCount、getMonthlyOrderReport、getStatusCounts、getSalesTrendData、getCustomerInsights、getFinanceStatistics、getSalesReport、getLatestOrders、getStatistics等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait OrderReportTrait
{
    /**
     * 获取用户各状态订单数量（用于个人中心/订单列表 tab 角标）
     *
     * @param int $userId
     * @return array
     */
    public function getOrderStatusCounts(int $userId): array
    {
        try {
            $base = $this->repository->query()->where('user_id', $userId);

            return [
                'all'              => (clone $base)->count(),
                'pending_payment'  => (clone $base)
                    ->where('status', Order::ORDER_STATUS_PENDING)
                    ->where('pay_status', Order::PAY_STATUS_UNPAID)
                    ->count(),
                'pending_shipment' => (clone $base)
                    ->where('status', Order::ORDER_STATUS_PENDING)
                    ->where('pay_status', Order::PAY_STATUS_PAID)
                    ->where('delivery_status', Order::DELIVERY_STATUS_UNSHIPPED)
                    ->count(),
                'pending_receipt'  => (clone $base)
                    ->where('status', Order::ORDER_STATUS_PENDING)
                    ->where('pay_status', Order::PAY_STATUS_PAID)
                    ->where('delivery_status', Order::DELIVERY_STATUS_SHIPPED)
                    ->where('receipt_status', Order::RECEIPT_STATUS_UNRECEIVED)
                    ->count(),
                'pending_review'   => (clone $base)
                    ->where('status', Order::ORDER_STATUS_COMPLETE)
                    ->where('pay_status', Order::PAY_STATUS_PAID)
                    ->where('is_comment', 0)
                    ->count(),
                'refunding'        => (clone $base)
                    ->where('pay_status', Order::REFUND_STATUS_REFUNDING)
                    ->count(),
            ];
        } catch (Exception $e) {
            $this->logError('获取订单状态计数失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return [
                'all' => 0, 'pending_payment' => 0, 'pending_shipment' => 0,
                'pending_receipt' => 0, 'pending_review' => 0, 'refunding' => 0,
            ];
        }
    }

    public function getTrackingOrders($appId, $pageSize = 20)
    {
        return $this->repository->getTrackingOrders((int) $appId, (int) $pageSize);
    }

    public function getCount($appId): int
    {
        return $this->repository->countByApp((int) $appId);
    }

    public function getTodayOrderCount($appId): int
    {
        return $this->repository->countTodayByApp((int) $appId);
    }

    public function getMonthlyOrderReport($appId): array
    {
        return $this->repository->getMonthlyDailyCounts((int) $appId);
    }

    public function getStatusCounts($appId): array
    {
        return $this->repository->getStatusCounts((int) $appId);
    }

    public function getSalesTrendData(int $days)
    {
        return $this->repository->getDailySalesTrend($days);
    }

    /**
     * 客户洞察指标（含衍生比率：avgOrderValue / repeatRate）
     */
    public function getCustomerInsights(int $appId)
    {
        $stats = $this->repository->getCustomerInsightStats($appId);

        $totalOrders = (int) $stats['total_orders'];
        $totalRevenue = (float) $stats['total_revenue'];
        $totalCustomers = (int) $stats['total_customers'];
        $repeatCustomers = (int) $stats['repeat_customers'];

        return array_merge($stats, [
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0,
            'repeat_customer_rate' => $totalCustomers > 0 ? round($repeatCustomers / $totalCustomers * 100, 1) : 0.0,
        ]);
    }

    /**
     * 财务统计（透传 Repository，再补占位字段）
     */
    public function getFinanceStatistics($appId): array
    {
        $stats = $this->repository->getPaidRevenueStats((int) $appId);

        return [
            'revenue_total'           => $stats['revenue_total'],
            'today_revenue'           => $stats['today_revenue'],
            'month_revenue'           => $stats['month_revenue'],
            'subscription_revenue'    => 0,
            'order_revenue'           => $stats['revenue_total'],
            'total_withdraw'          => 0,
            'earning_per_months'      => $stats['monthly'],
            'monthly_payment_charge'  => [],
            'monthly_withdraw_charge' => [],
        ];
    }

    /**
     * 销售报表（按日统计）
     */
    public function getSalesReport($appId, $startTs = null, $endTs = null): array
    {
        $days = 30;
        if ($startTs && $endTs) {
            $days = min(365, max(1, intval(($endTs - $startTs) / 86400) + 1));
        }

        $base = $this->repository->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date . ' 00:00:00');
            $dayEnd = strtotime($date . ' 23:59:59');

            $dayQuery = (clone $base)->whereBetween('created_at', [$dayStart, $dayEnd]);
            $orderCount = (clone $dayQuery)->count();
            $salesAmount = (clone $dayQuery)->where('pay_status', 20)->sum('pay_price');
            $refundAmount = (clone $dayQuery)->where('status', 5)->sum('pay_price');

            $data[] = [
                'date' => $date,
                'order_count' => $orderCount,
                'sales_amount' => round((float) $salesAmount, 2),
                'refund_amount' => round((float) $refundAmount, 2),
            ];
        }

        return $data;
    }

    public function getLatestOrders($appId, $limit = 20)
    {
        return $this->repository->getLatestOrders((int) $appId, (int) $limit);
    }

    public function getStatistics(int $appId): array
    {
        $todayOrders = $this->repository->countTodayByApp($appId);
        $yesterdayOrders = $this->repository->countYesterdayByApp($appId);
        $todayDiff = $todayOrders - $yesterdayOrders;
        $todayGrowth = $yesterdayOrders > 0 ? round(($todayOrders / $yesterdayOrders - 1) * 100, 1) : 0;

        $pendingPayment = $this->repository->countPendingPayment($appId);
        $totalOrders = $this->repository->countByApp($appId);
        $pendingPaymentPct = $totalOrders > 0 ? round($pendingPayment / $totalOrders * 100, 1) : 0;

        $pendingShip = $this->repository->countPendingShip($appId);
        $overdueUnshipped = $this->repository->countOverdueUnshipped($appId);

        $todayRevenue = $this->repository->getTodayRevenue($appId);
        $yesterdayRevenue = $this->repository->getYesterdayRevenue($appId);
        $revenueGrowth = $yesterdayRevenue > 0 ? round(($todayRevenue / $yesterdayRevenue - 1) * 100, 1) : 0;
        $paidTodayCount = $todayOrders > 0 ? $this->repository->countTodayByApp($appId) : 0;
        $avgOrderValue = $paidTodayCount > 0 ? round($todayRevenue / $paidTodayCount, 2) : 0;

        $pendingRefund = $this->repository->countPendingRefund($appId);

        return [
            'todayOrders' => $todayOrders,
            'todayDiff' => $todayDiff,
            'todayGrowth' => $todayGrowth,
            'pendingPayment' => $pendingPayment,
            'pendingPaymentPct' => $pendingPaymentPct,
            'avgStayMin' => 0,
            'pendingShip' => $pendingShip,
            'urgentShip' => $pendingShip,
            'overdueUnshipped' => $overdueUnshipped,
            'todayRevenue' => $todayRevenue,
            'revenueGrowth' => $revenueGrowth,
            'avgOrderValue' => $avgOrderValue,
            'pendingRefund' => $pendingRefund,
        ];
    }
}
