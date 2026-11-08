<?php

namespace app\service;
use app\repository\OrderRepository;
use app\model\User;
use Exception;

/**
 * UserStatisticsTrait：从 UserService.php 抽离的getUserStats、getReferralList、getUserReport、getLatestUsers、getUserDashboard等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait UserStatisticsTrait
{
    /**
     * 用户综合统计（消费/推荐/收藏/浏览/搜索/评价/优惠券）
     *
     * 多源聚合，无法纯下沉到单一 Repository，仍在 Service 层协调；
     * 关联 count 走 User 模型已定义的 hasMany 关系（Eloquent 默认走索引）。
     */
    public function getUserStats($userId): array
    {
        try {
            $user = $this->findOrFail($userId);

            return [
                'user_info'       => $user->toArray(),
                'consumption'     => $this->repository->getUserConsumptionStats($userId, $user->app_id),
                'referral'        => $this->repository->getUserReferralStats($userId, $user->app_id),
                'favorites_count' => $user->favorites()->count(),
                'views_count'     => $user->views()->count(),
                'searches_count'  => $user->searches()->count(),
                'comments_count'  => $user->comments()->count(),
                'coupons_count'   => $user->coupons()->count(),
            ];
        } catch (Exception $e) {
            $this->logError('获取用户统计信息失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 推荐人下级列表（指定层级）
     */
    public function getReferralList($userId, $level = 1, $page = 1, $limit = 15)
    {
        try {
            $this->findOrFail($userId);  // 校验推荐人存在
            return $this->repository->getReferralsPaginated((int) $userId, (int) $level, (int) $page, (int) $limit);
        } catch (Exception $e) {
            $this->logError('获取用户推荐列表失败',
                compact('userId', 'level', 'page', 'limit') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 用户报表（按日统计）
     */
    public function getUserReport($appId, $startTs = null, $endTs = null): array
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

            $data[] = [
                'date' => $date,
                'new_users' => (clone $base)->whereBetween('created_at', [$dayStart, $dayEnd])->count(),
                'active_users' => (clone $base)->whereBetween('last_login_at', [$dayStart, $dayEnd])->count(),
                'order_users' => 0,
            ];
        }

        return $data;
    }

    /**
     * 最新注册用户
     */
    public function getLatestUsers($appId, $limit = 10)
    {
        $query = $this->repository->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('id', 'desc')->limit($limit)->get();
    }

    /**
     * 用户仪表板（真实数据）
     */
    public function getUserDashboard($appId): array
    {
        $base = $this->repository->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        $now = time();
        $thisMonthStart = strtotime(date('Y-m-01') . ' 00:00:00');
        $lastMonthStart = strtotime('first day of last month 00:00:00');
        $lastMonthEnd = strtotime('last day of last month 23:59:59');
        $thirtyDaysAgo = $now - 86400 * 30;
        $ninetyDaysAgo = $now - 86400 * 90;

        // --- Stats ---
        $totalUsers = (clone $base)->count();
        $totalUsersLastMonth = (clone $base)->where('created_at', '<', $thisMonthStart)->count();
        $totalUsersTrend = $totalUsersLastMonth > 0 ? round(($totalUsers - $totalUsersLastMonth) / $totalUsersLastMonth * 100, 1) : 0;

        $newUsers = (clone $base)->where('created_at', '>=', $thisMonthStart)->count();
        $newUsersLastMonth = (clone $base)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->count();
        $newUsersTrend = $newUsersLastMonth > 0 ? round(($newUsers - $newUsersLastMonth) / $newUsersLastMonth * 100, 1) : 0;

        $activeUsers = (clone $base)->where('last_login_at', '>=', $thirtyDaysAgo)->count();
        $activeRate = $totalUsers > 0 ? round($activeUsers / $totalUsers, 4) : 0;
        $activeUsersLastMonth = (clone $base)->where('last_login_at', '>=', $thirtyDaysAgo - 86400 * 30)->where('last_login_at', '<', $thirtyDaysAgo)->count();
        $activeRateLastMonth = $totalUsers > 0 ? round($activeUsersLastMonth / $totalUsers, 4) : 0;
        $activeRateTrend = $activeRateLastMonth > 0 ? round(($activeRate - $activeRateLastMonth) / $activeRateLastMonth * 100, 1) : 0;

        // Avg order value
        $orderBase = $this->orderRepo->query();
        if ($appId > 0) {
            $orderBase->where('app_id', $appId);
        }
        $avgOrderValue = (clone $orderBase)->where('pay_status', 20)->where('created_at', '>=', $thisMonthStart)->avg('pay_price') ?? 0;
        $avgOrderValueLastMonth = (clone $orderBase)->where('pay_status', 20)->whereBetween('created_at', [$lastMonthStart, $lastMonthEnd])->avg('pay_price') ?? 0;
        $avgOrderValueTrend = $avgOrderValueLastMonth > 0 ? round(($avgOrderValue - $avgOrderValueLastMonth) / $avgOrderValueLastMonth * 100, 1) : 0;

        // --- Chart (last 12 months new users) ---
        $chart = [];
        for ($i = 11; $i >= 0; $i--) {
            $monthStart = strtotime(date('Y-m-01', strtotime("-{$i} months")) . ' 00:00:00');
            $monthEnd = strtotime('last day of ' . date('Y-m', $monthStart) . ' 23:59:59');
            $count = (clone $base)->whereBetween('created_at', [$monthStart, $monthEnd])->count();
            $chart[] = [
                'label' => date('n', $monthStart) . '',
                'value' => $count,
            ];
        }

        // --- Segments ---
        $newCount = (clone $base)->where('created_at', '>=', $thirtyDaysAgo)->count();
        $vipCount = (clone $base)->where('level_id', '>', 1)->count();
        $regularCount = (clone $base)->where('level_id', 1)->where('created_at', '<', $thirtyDaysAgo)->count();
        $inactiveCount = (clone $base)->where(function ($q) use ($ninetyDaysAgo) {
            $q->whereNull('last_login_at')->orWhere('last_login_at', '<', $ninetyDaysAgo);
        })->count();

        $segments = [];
        foreach ([
            ['key' => 'new', 'count' => $newCount],
            ['key' => 'vip', 'count' => $vipCount],
            ['key' => 'regular', 'count' => $regularCount],
            ['key' => 'inactive', 'count' => $inactiveCount],
        ] as $seg) {
            $seg['percent'] = $totalUsers > 0 ? round($seg['count'] / $totalUsers * 100, 1) : 0;
            $segments[] = $seg;
        }

        // --- Latest users ---
        $latestUsers = (clone $base)->orderBy('id', 'desc')->limit(10)->get();
        $orderRepo = new OrderRepository();
        // 批量统计各用户的订单数与消费额，避免逐条查询（N+1）
        $orderAgg = $orderRepo->aggregateByUserIds(
            array_map(fn($u) => (int) $u->id, $latestUsers->all())
        );
        $users = [];
        foreach ($latestUsers as $u) {
            $agg = $orderAgg[(string) $u->id] ?? ['order_count' => 0, 'total_spent' => 0.0];
            $orderCount = $agg['order_count'];
            $totalSpent = $agg['total_spent'];
            $users[] = [
                'id' => $u->id,
                'nickname' => $u->nickname,
                'username' => $u->phone ?: '',
                'registered_at' => $u->created_at ? date('Y-m-d', is_numeric($u->created_at) ? $u->created_at : strtotime($u->created_at)) : '-',
                'created_at' => $u->created_at ? date('Y-m-d', is_numeric($u->created_at) ? $u->created_at : strtotime($u->created_at)) : '-',
                'order_count' => $orderCount,
                'total_spent' => round((float) $totalSpent, 2),
                'status' => $u->deleted_at > 0 ? 'inactive' : 'active',
            ];
        }

        return [
            'stats' => [
                'total_users' => $totalUsers,
                'total_users_trend' => $totalUsersTrend,
                'new_users' => $newUsers,
                'new_users_trend' => $newUsersTrend,
                'active_rate' => $activeRate,
                'active_rate_trend' => $activeRateTrend,
                'avg_order_value' => round((float) $avgOrderValue, 2),
                'avg_order_value_trend' => $avgOrderValueTrend,
            ],
            'chart' => $chart,
            'segments' => $segments,
            'users' => $users,
        ];
    }
}
