<?php

namespace app\repository;
use app\model\Order;

/**
 * OrderStatisticRepositoryTrait：从 OrderRepository.php 抽离的getOrderCountByStatus、getStatusCounts、countByApp、countYesterdayByApp、countPendingPayment、countPendingShip、countOverdueUnshipped、getTodayRevenue、getYesterdayRevenue、countPendingRefund、countTodayByApp、getMonthlyDailyCounts、getOrderCountByYear、getDailySalesTrend、getCustomerInsightStats、getPaidRevenueStats等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\repository
 */
trait OrderStatisticRepositoryTrait
{
    /**
     * 指定状态订单计数
     */
    public function getOrderCountByStatus(int $appId, $status): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', $status)
            ->count();
    }

    /**
     * 多状态计数（后台首页指标）
     * @return array{pending,cancelled,completed,unpaid,paid,unshipped,shipped: int}
     */
    public function getStatusCounts(int $appId): array
    {
        $base = $this->query()->where('app_id', $appId);
        return [
            'pending'    => (clone $base)->where('status', 10)->count(),
            'cancelled'  => (clone $base)->where('status', 20)->count(),
            'completed'  => (clone $base)->where('status', 30)->count(),
            'unpaid'     => (clone $base)->where('pay_status', 10)->count(),
            'paid'       => (clone $base)->where('pay_status', 20)->count(),
            'unshipped'  => (clone $base)->where('delivery_status', 10)->count(),
            'shipped'    => (clone $base)->where('delivery_status', 20)->count(),
        ];
    }

    /**
     * 总订单数
     */
    public function countByApp(int $appId): int
    {
        return $this->query()->where('app_id', $appId)->count();
    }

    public function countYesterdayByApp(int $appId): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('created_at', '>=', strtotime('yesterday'))
            ->where('created_at', '<', strtotime('today'))
            ->count();
    }

    public function countPendingPayment(int $appId): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', Order::ORDER_STATUS_PENDING)
            ->where('pay_status', Order::PAY_STATUS_UNPAID)
            ->count();
    }

    public function countPendingShip(int $appId): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', Order::ORDER_STATUS_PENDING)
            ->where('pay_status', Order::PAY_STATUS_PAID)
            ->where('delivery_status', Order::DELIVERY_STATUS_UNSHIPPED)
            ->count();
    }

    public function countOverdueUnshipped(int $appId, int $hours = 48): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', Order::ORDER_STATUS_PENDING)
            ->where('pay_status', Order::PAY_STATUS_PAID)
            ->where('delivery_status', Order::DELIVERY_STATUS_UNSHIPPED)
            ->where('pay_time', '<', time() - $hours * 3600)
            ->count();
    }

    public function getTodayRevenue(int $appId): float
    {
        return (float) $this->query()
            ->where('app_id', $appId)
            ->where('pay_status', Order::PAY_STATUS_PAID)
            ->where('pay_time', '>=', strtotime('today'))
            ->sum('pay_price');
    }

    public function getYesterdayRevenue(int $appId): float
    {
        return (float) $this->query()
            ->where('app_id', $appId)
            ->where('pay_status', Order::PAY_STATUS_PAID)
            ->where('pay_time', '>=', strtotime('yesterday'))
            ->where('pay_time', '<', strtotime('today'))
            ->sum('pay_price');
    }

    public function countPendingRefund(int $appId): int
    {
        return \app\model\AfterSales::where('app_id', $appId)
            ->where('status', 10)
            ->count();
    }

    /**
     * 今日订单数
     */
    public function countTodayByApp(int $appId): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('created_at', '>=', strtotime('today'))
            ->count();
    }

    /**
     * 月度日订单数（本月每天的下单量）
     * @return array [{date, order_count}, ...]
     */
    public function getMonthlyDailyCounts(int $appId): array
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('created_at', '>=', strtotime('first day of this month'))
            ->selectRaw("DATE_FORMAT(FROM_UNIXTIME(created_at), '%Y-%m-%d') as date, count(*) as order_count")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * 按年统计订单数
     * @return array<int|string, array{year:int, total:int}>
     */
    public function getOrderCountByYear(int $appId): array
    {
        return $this->query()
            ->where('app_id', $appId)
            ->selectRaw("YEAR(FROM_UNIXTIME(created_at)) as year, COUNT(*) as total")
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->keyBy('year')
            ->toArray();
    }

    /**
     * 日销售趋势（近 N 天）
     * @return array<string, array{date,total,count}>
     */
    public function getDailySalesTrend(int $days, ?int $appId = null): array
    {
        $q = $this->query()
            ->selectRaw("FROM_UNIXTIME(created_at, '%Y-%m-%d') as date, SUM(pay_price) as total, COUNT(*) as count")
            ->where('created_at', '>=', time() - $days * 86400);

        if ($appId !== null && $appId > 0) {
            $q->where('app_id', $appId);
        }

        return $q->groupBy('date')->orderBy('date')->get()->keyBy('date')->toArray();
    }

    /**
     * 客户洞察聚合（原始指标，衍生比率由 Service 计算）
     * @return array{total_orders,total_revenue,repeat_customers,total_customers: int|float}
     */
    public function getCustomerInsightStats(int $appId): array
    {
        $base = $this->query()->where('app_id', $appId);

        return [
            'total_orders' => (clone $base)->count(),
            'total_revenue' => (float) (clone $base)->sum('pay_price'),
            'repeat_customers' => (clone $base)
                ->selectRaw('user_id, COUNT(*) as order_count')
                ->groupBy('user_id')
                ->having('order_count', '>', 1)
                ->get()
                ->count(),
            'total_customers' => (clone $base)->distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * 已支付订单营收聚合（总/今日/月，月度分布）
     * @return array{revenue_total,today_revenue,month_revenue: float, monthly: array}
     */
    public function getPaidRevenueStats(int $appId): array
    {
        $todayStart = strtotime('today');
        $monthStart = strtotime('first day of this month');

        $paidBase = $this->query()
            ->where('app_id', $appId)
            ->where('pay_status', Order::PAY_STATUS_PAID);

        return [
            'revenue_total' => (float) (clone $paidBase)->sum('pay_price'),
            'today_revenue' => (float) (clone $paidBase)->where('created_at', '>=', $todayStart)->sum('pay_price'),
            'month_revenue' => (float) (clone $paidBase)->where('created_at', '>=', $monthStart)->sum('pay_price'),
            'monthly' => (clone $paidBase)
                ->selectRaw("DATE_FORMAT(FROM_UNIXTIME(created_at), '%Y-%m') as month, SUM(pay_price) as total")
                ->groupBy('month')->orderBy('month')
                ->pluck('total', 'month')->toArray(),
        ];
    }
}
