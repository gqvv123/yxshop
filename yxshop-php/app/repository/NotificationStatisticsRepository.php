<?php

namespace app\repository;

use app\model\NotificationStatistics;

/**
 * 通知统计仓储类
 */
class NotificationStatisticsRepository extends BaseRepository
{
    protected $model = NotificationStatistics::class;

    /**
     * 获取统计数据
     */
    public function getStatistics($startDate = null, $endDate = null, $sceneId = null, $templateId = null, $sendType = null, $appId = 0)
    {
        $query = $this->query();

        if ($startDate) {
            $query->where('statistics_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('statistics_date', '<=', $endDate);
        }

        if ($sceneId !== null) {
            $query->where('scene_id', $sceneId);
        }

        if ($templateId !== null) {
            $query->where('template_id', $templateId);
        }

        if ($sendType !== null) {
            $query->where('send_type', $sendType);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('statistics_date', 'desc')->get();
    }

    /**
     * 获取总体统计
     */
    public function getOverallStats($startDate = null, $endDate = null, $appId = 0)
    {
        $query = $this->query();

        if ($startDate) {
            $query->where('statistics_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('statistics_date', '<=', $endDate);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total_count' => $query->sum('total_count'),
            'success_count' => $query->sum('success_count'),
            'failed_count' => $query->sum('failed_count'),
            'success_rate' => $this->calculateSuccessRate($query->sum('total_count'), $query->sum('success_count')),
        ];
    }

    /**
     * 获取按类型统计
     */
    public function getStatsByType($startDate = null, $endDate = null, $appId = 0)
    {
        $query = $this->query();

        if ($startDate) {
            $query->where('statistics_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('statistics_date', '<=', $endDate);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->selectRaw('send_type, SUM(total_count) as total_count, SUM(success_count) as success_count, SUM(failed_count) as failed_count')
            ->groupBy('send_type')
            ->get();
    }

    /**
     * 获取按场景统计
     */
    public function getStatsByScene($startDate = null, $endDate = null, $appId = 0)
    {
        $query = $this->query();

        if ($startDate) {
            $query->where('statistics_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('statistics_date', '<=', $endDate);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->selectRaw('scene_id, SUM(total_count) as total_count, SUM(success_count) as success_count, SUM(failed_count) as failed_count')
            ->groupBy('scene_id')
            ->get();
    }

    /**
     * 计算成功率
     */
    private function calculateSuccessRate($total, $success)
    {
        if ($total == 0) {
            return 0;
        }
        return round(($success / $total) * 100, 2);
    }
}
