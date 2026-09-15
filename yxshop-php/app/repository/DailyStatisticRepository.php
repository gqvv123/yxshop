<?php

namespace app\repository;

use app\model\DailyStatistic;

/**
 * 每日统计仓储
 */
class DailyStatisticRepository extends BaseRepository
{
    protected $model = DailyStatistic::class;

    /**
     * 按日期获取统计
     */
    public function getByDate(int $statDate, int $appId = 0)
    {
        $query = $this->query()->where('stat_date', $statDate);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->first();
    }

    /**
     * 获取日期范围统计
     */
    public function getByDateRange(int $startDate, int $endDate, int $appId = 0)
    {
        $query = $this->query()
            ->where('stat_date', '>=', $startDate)
            ->where('stat_date', '<=', $endDate);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('stat_date', 'asc')->get();
    }

    /**
     * 获取最近N天统计
     */
    public function getRecentDays(int $days = 7, int $appId = 0)
    {
        $startDate = date('Ymd', strtotime("-{$days} days"));
        $endDate = date('Ymd');
        return $this->getByDateRange((int) $startDate, (int) $endDate, $appId);
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('stat_date', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (!empty($filters['start_date'])) {
            $query->where('stat_date', '>=', (int) $filters['start_date']);
        }
        if (!empty($filters['end_date'])) {
            $query->where('stat_date', '<=', (int) $filters['end_date']);
        }
        return $query->paginate($pageSize);
    }

    /**
     * 获取汇总统计
     */
    public function getSummary(int $startDate, int $endDate, int $appId = 0): array
    {
        $base = $this->query()
            ->where('stat_date', '>=', $startDate)
            ->where('stat_date', '<=', $endDate);
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        return [
            'total_users' => (clone $base)->sum('new_users'),
            'total_orders' => (clone $base)->sum('order_count'),
            'total_amount' => (clone $base)->sum('order_amount'),
            'total_pay' => (clone $base)->sum('pay_amount'),
            'total_refund' => (clone $base)->sum('refund_amount'),
            'total_views' => (clone $base)->sum('page_views'),
        ];
    }
}