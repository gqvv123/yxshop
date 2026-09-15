<?php

namespace app\repository;

use app\model\User;

/**
 * 用户仓储类
 */
class UserRepository extends BaseRepository
{
    protected $model = User::class;

    /**
     * 根据手机号查找用户
     */
    public function findByPhone($phone, $appId = 0)
    {
        return $this->query()
            ->where('phone', $phone)
            ->where('app_id', $appId ?? 0)
            ->first();
    }

    /**
     * 根据OpenID查找用户
     */
    public function findByOpenId($openId, $appId = 0)
    {
        return $this->query()
            ->where('openid', $openId)
            ->where('app_id', $appId ?? 0)
            ->first();
    }

    /**
     * 根据邮箱查找用户
     */
    public function findByEmail(string $email, int $appId = 0)
    {
        return $this->query()
            ->where('email', $email)
            ->where('app_id', $appId)
            ->first();
    }

    /**
     * 根据昵称查找用户（精确匹配）
     */
    public function findByNickname(string $nickname, int $appId = 0)
    {
        return $this->query()
            ->where('nickname', $nickname)
            ->where('app_id', $appId)
            ->first();
    }

    /**
     * 根据推荐人ID查找用户
     */
    public function findByRefereeId($refereeId, $appId = 0)
    {
        return $this->query()
            ->where('referrer_id', $refereeId)
            ->where('app_id', $appId ?? 0)
            ->get();
    }

    /**
     * 根据等级查找用户
     */
    public function findByLevel($levelId, $appId = 0)
    {
        return $this->query()
            ->where('level_id', $levelId)
            ->where('app_id', $appId ?? 0)
            ->get();
    }

    /**
     * 查找分销商
     */
    public function findDealers($appId = 0)
    {
        return $this->query()
            ->where('is_dealer', 1)
            ->where('app_id', $appId ?? 0)
            ->get();
    }

    /**
     * 查找VIP用户
     */
    public function findVipUsers($appId = 0)
    {
        return $this->query()
            ->where('level_id', '>', 1)
            ->where('app_id', $appId ?? 0)
            ->get();
    }

    /**
     * 获取用户统计信息
     */
    public function getUserStats($appId = 0)
    {
        $base = $this->query()
            ->where('app_id', $appId ?? 0);

        // 关键：每项 clone 后再 where，避免在同一 Builder 上累加条件
        $today = strtotime('today');
        return [
            'total' => $base->count(),
            'active' => (clone $base)->count(),
            'vip' => (clone $base)->where('level_id', '>', 1)->count(),
            'dealers' => (clone $base)->where('is_dealer', 1)->count(),
            'new_today' => (clone $base)->where('created_at', '>=', $today)->count(),
            'new_week' => (clone $base)->where('created_at', '>=', strtotime('-7 days'))->count(),
            'new_month' => (clone $base)->where('created_at', '>=', strtotime('-30 days'))->count(),
        ];
    }

    /**
     * 获取用户排行榜（deleted_at 由全局 scope 过滤）
     */
    public function getUserRanking($type = 'total_spent', $limit = 10, $appId = 0)
    {
        // type 白名单防止 SQL 注入
        $allowed = ['total_spent', 'integral', 'commission', 'login_count', 'created_at'];
        $sortField = in_array($type, $allowed, true) ? $type : 'total_spent';

        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy($sortField, 'desc')->limit($limit)->get();
    }

    /**
     * 搜索用户（deleted_at 已由全局 scope 过滤）
     */
    public function searchUsers($keyword, $appId = 0, $page = 1, $limit = 15)
    {
        $base = $this->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], (string) $keyword);
        $base->where(function ($q) use ($escaped) {
            $q->where('nickname', 'like', "%{$escaped}%")
              ->orWhere('phone', 'like', "%{$escaped}%")
              ->orWhere('id', 'like', "%{$escaped}%");
        });

        $total = (clone $base)->count();
        $offset = ($page - 1) * $limit;
        $items = $base->offset($offset)->limit($limit)->get();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $limit, $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }

    /**
     * 获取用户消费统计
     */
    public function getUserConsumptionStats($userId, $appId = 0)
    {
        $user = $this->find($userId);
        if (!$user) {
            return null;
        }

        $query = $this->query()->where('id', $userId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total_consumption' => $user->total_spent,
            'total_orders' => $user->orders()->count(),
            'total_integral' => $user->integral,
            'total_profit' => $user->commission,
            'first_order_time' => $user->orders()->min('created_at'),
            'last_order_time' => $user->orders()->max('created_at'),
            'avg_order_amount' => $user->orders()->avg('pay_price'),
        ];
    }

    /**
     * 获取用户推荐统计
     */
    public function getUserReferralStats($userId, $appId = 0)
    {
        $user = $this->find($userId);
        if (!$user) {
            return null;
        }

        $query = $this->query()->where('referrer_id', $userId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total_referrals' => $query->count(),
            'first_level' => $query->where('level', 1)->count(),
            'second_level' => $query->where('level', 2)->count(),
            'third_level' => $query->where('level', 3)->count(),
            'referral_consumption' => $user->referrer_spent,
        ];
    }

    /**
     * 批量更新用户等级
     */
    public function batchUpdateLevel($userIds, $levelId)
    {
        return $this->updateWhere(['id' => $userIds], ['level_id' => $levelId]);
    }

    /**
     * 批量设置分销商
     */
    public function batchSetDealer($userIds, $isDealer = 1)
    {
        return $this->updateWhere(['id' => $userIds], ['is_dealer' => $isDealer]);
    }

    /**
     * 获取用户增长趋势
     */
    public function getUserGrowthTrend($days = 30, $appId = 0)
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $startTime = strtotime($date . ' 00:00:00');
            $endTime = strtotime($date . ' 23:59:59');
            
            $count = $query->whereBetween('created_at', [$startTime, $endTime])->count();
            $data[] = [
                'date' => $date,
                'count' => $count
            ];
        }

        return $data;
    }

    /**
     * 获取用户地域分布
     */
    public function getUserRegionDistribution(int $appId = 0)
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->selectRaw('province, COUNT(*) as count')
                    ->groupBy('province')
                    ->orderBy('count', 'desc')
                    ->get();
    }

    /**
     * 获取用户性别分布
     */
    public function getUserGenderDistribution(int $appId = 0)
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->selectRaw('gender, COUNT(*) as count')
                    ->groupBy('gender')
                    ->get();
    }

    /**
     * 获取用户年龄分布
     */
    public function getUserAgeDistribution(int $appId = 0)
    {
        $query = $this->query()->whereNotNull('birthday');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        $users = $query->get();
        $distribution = [
            '0-18' => 0,
            '19-25' => 0,
            '26-35' => 0,
            '36-45' => 0,
            '46-55' => 0,
            '55+' => 0
        ];

        foreach ($users as $user) {
            $age = date('Y') - date('Y', (int) $user->birthday);
            if ($age <= 18) {
                $distribution['0-18']++;
            } elseif ($age <= 25) {
                $distribution['19-25']++;
            } elseif ($age <= 35) {
                $distribution['26-35']++;
            } elseif ($age <= 45) {
                $distribution['36-45']++;
            } elseif ($age <= 55) {
                $distribution['46-55']++;
            } else {
                $distribution['55+']++;
            }
        }

        return $distribution;
    }

    // ---------------- 列表 / 分页 ----------------

    /**
     * 加行锁查用户（用于积分/余额并发操作）
     */
    public function lockForUpdate(int $userId): ?User
    {
        return $this->query()->where('id', $userId)->lockForUpdate()->first();
    }

    /**
     * 原子增加积分
     */
    public function incrementIntegral(int $userId, int $amount): int
    {
        return $this->query()->where('id', $userId)->increment('integral', $amount);
    }

    /**
     * 原子扣减积分（WHERE integral >= amount 防透支）
     */
    public function decrementIntegral(int $userId, int $amount): int
    {
        return $this->query()
            ->where('id', $userId)
            ->where('integral', '>=', $amount)
            ->decrement('integral', $amount);
    }

    /**
     * 根据推荐人ID统计直接推荐人数
     */
    public function countByReferrerId(int $referrerId): int
    {
        return $this->query()->where('referrer_id', $referrerId)->count();
    }

    /**
     * 根据推荐人ID取下级用户ID列表
     */
    public function getIdsByReferrerId(int $referrerId): array
    {
        return $this->query()->where('referrer_id', $referrerId)->pluck('id')->toArray();
    }

    /**
     * 根据推荐人ID列表统计间接推荐人数
     */
    public function countByReferrerIds(array $referrerIds): int
    {
        if (empty($referrerIds)) {
            return 0;
        }
        return $this->query()->whereIn('referrer_id', $referrerIds)->count();
    }

    /**
     * 推荐人下级分页列表（含订单统计）
     */
    public function getTeamPaginatedByReferrer(int $referrerId, int $page = 1, int $pageSize = 20): array
    {
        $base = $this->query()->where('referrer_id', $referrerId)->orderBy('created_at', 'desc');
        $total = (clone $base)->count();
        $list = $base->forPage($page, $pageSize)->get();

        return [
            'data' => $list,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
            'last_page' => (int) ceil($total / $pageSize),
        ];
    }

    /**
     * 按 app 分页列表（后台）
     */
    public function getPaginatedByApp(int $appId, int $pageSize = 20)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->orderBy('id', 'desc')
            ->paginate($pageSize);
    }

    /**
     * 按 app 用户数
     */
    public function countByApp(int $appId): int
    {
        return $this->query()->where('app_id', $appId)->count();
    }

    /**
     * 推荐人下级列表（分页含元数据）
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getReferralsPaginated(int $referrerId, int $level = 1, int $page = 1, int $limit = 15)
    {
        $base = $this->query()
            ->where('referrer_id', $referrerId)
            ->where('level', $level);

        $total = (clone $base)->count();
        $offset = ($page - 1) * $limit;
        $items = $base->offset($offset)->limit($limit)->get();

        return new \Illuminate\Pagination\LengthAwarePaginator(
            $items, $total, $limit, $page,
            ['path' => request()->url(), 'pageName' => 'page']
        );
    }
}

