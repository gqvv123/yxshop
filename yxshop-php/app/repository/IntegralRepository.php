<?php

namespace app\repository;

use app\model\Integral;

/**
 * 积分流水仓储（yxshop_integral_log）
 * 字段对齐：amount / before_balance / after_balance / type / is_expired
 */
class IntegralRepository extends BaseRepository
{
    protected $model = Integral::class;

    /**
     * 用户全部积分流水
     */
    public function getByUserId($userId)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 用户积分流水分页（可选 type 筛选）
     */
    public function getPaginatedByUser(int $userId, ?int $type = null, int $page = 1, int $pageSize = 20)
    {
        $query = $this->query()->where('user_id', $userId);
        if ($type !== null) {
            $query->where('type', $type);
        }
        return $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 用户积分聚合统计（earned / spent / 最近一条 after_balance）
     */
    public function getUserAggregateStats(int $userId): array
    {
        $base = $this->query()->where('user_id', $userId);

        return [
            'total_earned' => (float) (clone $base)->where('amount', '>', 0)->sum('amount'),
            'total_spent'  => (float) abs((float) (clone $base)->where('amount', '<', 0)->sum('amount')),
            'last_balance' => (int) ((clone $base)->orderBy('id', 'desc')->value('after_balance') ?? 0),
        ];
    }

    /**
     * 用户月度获得积分合计
     */
    public function sumMonthEarnedByUser(int $userId, int $monthStart): int
    {
        return (int) $this->query()
            ->where('user_id', $userId)
            ->where('amount', '>', 0)
            ->where('created_at', '>=', $monthStart)
            ->sum('amount');
    }

    /**
     * 取过期未处理的获得记录
     */
    public function getExpiredEarnedLogs(int $userId, int $expireTime)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('amount', '>', 0)
            ->where('is_expired', 0)
            ->where('created_at', '<=', $expireTime)
            ->get();
    }

    /**
     * 取所有有过期记录的用户 ID（用于批量过期任务）
     * @return array<int>
     */
    public function getUsersWithExpiredLogs(int $expireTime): array
    {
        return $this->query()
            ->where('amount', '>', 0)
            ->where('is_expired', 0)
            ->where('created_at', '<=', $expireTime)
            ->distinct()
            ->pluck('user_id')
            ->toArray();
    }
}
