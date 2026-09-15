<?php

namespace app\repository;

use app\model\MonthlyStatistic;

/**
 * 每月统计仓储
 */
class MonthlyStatisticRepository extends BaseRepository
{
    protected $model = MonthlyStatistic::class;

    /**
     * 按月份获取统计
     */
    public function getByMonth(int $statMonth, int $appId = 0)
    {
        $query = $this->query()->where('stat_month', $statMonth);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->first();
    }

    /**
     * 获取月份范围统计
     */
    public function getByMonthRange(int $startMonth, int $endMonth, int $appId = 0)
    {
        $query = $this->query()
            ->where('stat_month', '>=', $startMonth)
            ->where('stat_month', '<=', $endMonth);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('stat_month', 'asc')->get();
    }

    /**
     * 获取最近N月统计
     */
    public function getRecentMonths(int $months = 12, int $appId = 0)
    {
        $startMonth = date('Ym', strtotime("-{$months} months"));
        $endMonth = date('Ym');
        return $this->getByMonthRange((int) $startMonth, (int) $endMonth, $appId);
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('stat_month', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (!empty($filters['start_month'])) {
            $query->where('stat_month', '>=', (int) $filters['start_month']);
        }
        if (!empty($filters['end_month'])) {
            $query->where('stat_month', '<=', (int) $filters['end_month']);
        }
        return $query->paginate($pageSize);
    }

    /**
     * 获取汇总统计
     */
    public function getSummary(int $startMonth, int $endMonth, int $appId = 0): array
    {
        $base = $this->query()
            ->where('stat_month', '>=', $startMonth)
            ->where('stat_month', '<=', $endMonth);
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        return [
            'total_new_users' => (clone $base)->sum('new_users'),
            'total_users' => (clone $base)->sum('total_users'),
            'total_orders' => (clone $base)->sum('order_count'),
            'total_amount' => (clone $base)->sum('order_amount'),
            'total_pay' => (clone $base)->sum('pay_amount'),
            'total_refund' => (clone $base)->sum('refund_amount'),
        ];
    }
}