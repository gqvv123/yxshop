<?php

namespace app\repository;

use app\model\AiUsageLog;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * AI 使用记录仓储
 */
class AiUsageLogRepository extends BaseRepository
{
    public function __construct(?AiUsageLog $model = null)
    {
        parent::__construct($model ?? new AiUsageLog());
    }

    /**
     * 按应用分页查询使用记录
     */
    public function getListByApp(int $appId, int $pageSize = 20, array $filters = []): LengthAwarePaginator
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (!empty($filters['feature'])) {
            $query->where('feature', $filters['feature']);
        }
        if (!empty($filters['provider'])) {
            $query->where('provider', $filters['provider']);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }
        if (!empty($filters['start_date'])) {
            $query->where('created_at', '>=', strtotime($filters['start_date'] . ' 00:00:00'));
        }
        if (!empty($filters['end_date'])) {
            $query->where('created_at', '<=', strtotime($filters['end_date'] . ' 23:59:59'));
        }

        return $query->orderBy('created_at', 'desc')->paginate($pageSize);
    }

    /**
     * 获取应用算力消耗统计
     */
    public function getUsageStats(int $appId): array
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        $totalCalls = (clone $query)->count();
        $successCalls = (clone $query)->where('status', AiUsageLog::STATUS_SUCCESS)->count();
        $failedCalls = $totalCalls - $successCalls;
        $totalCost = (clone $query)->where('status', AiUsageLog::STATUS_SUCCESS)->sum('cost_calls');
        $totalInputTokens = (clone $query)->where('status', AiUsageLog::STATUS_SUCCESS)->sum('input_tokens');
        $totalOutputTokens = (clone $query)->where('status', AiUsageLog::STATUS_SUCCESS)->sum('output_tokens');
        $totalAmount = (clone $query)->where('status', AiUsageLog::STATUS_SUCCESS)->sum('cost_amount');

        // 按功能分组统计
        $byFeature = (clone $query)
            ->selectRaw('feature, COUNT(*) as count, SUM(cost_calls) as total_cost')
            ->groupBy('feature')
            ->get()
            ->keyBy('feature')
            ->toArray();

        // 今日统计
        $todayStart = strtotime(date('Y-m-d') . ' 00:00:00');
        $todayCalls = (clone $query)->where('created_at', '>=', $todayStart)->count();

        return [
            'total_calls'         => $totalCalls,
            'success_calls'       => $successCalls,
            'failed_calls'        => $failedCalls,
            'total_cost_calls'    => (int) $totalCost,
            'total_input_tokens'  => (int) $totalInputTokens,
            'total_output_tokens' => (int) $totalOutputTokens,
            'total_amount'        => round((float) $totalAmount, 4),
            'today_calls'         => $todayCalls,
            'by_feature'          => $byFeature,
        ];
    }

    /**
     * 获取最近 N 天的使用趋势
     */
    public function getUsageTrend(int $appId, int $days = 30): array
    {
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date . ' 00:00:00');
            $dayEnd = strtotime($date . ' 23:59:59');

            $query = $this->query();
            if ($appId > 0) {
                $query->where('app_id', $appId);
            }
            $dayQuery = (clone $query)->whereBetween('created_at', [$dayStart, $dayEnd]);

            $data[] = [
                'date'       => $date,
                'calls'      => (clone $dayQuery)->count(),
                'cost_calls' => (int) (clone $dayQuery)->where('status', AiUsageLog::STATUS_SUCCESS)->sum('cost_calls'),
            ];
        }
        return $data;
    }
}
