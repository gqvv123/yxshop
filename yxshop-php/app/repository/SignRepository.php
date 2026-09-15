<?php

namespace app\repository;

use app\model\Sign;

/**
 * 签到仓储类
 */
class SignRepository extends BaseRepository
{
    protected $model = Sign::class;

    /**
     * 获取用户签到记录
     */
    public function getUserSigns($userId, $appId = 0, $limit = 30)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->orderBy('sign_date', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 获取用户今日签到
     */
    public function getUserTodaySign($userId, $appId = 0)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('sign_date', date('Y-m-d'));

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->first();
    }

    /**
     * 获取用户连续签到天数
     */
    public function getUserContinuousDays($userId, $appId = 0)
    {
        $signs = $this->query()
            ->where('user_id', $userId)
            ->where('app_id', $appId)
            ->orderBy('sign_date', 'desc')
            ->get();

        $continuous = 0;
        $lastDate = null;

        foreach ($signs as $sign) {
            if ($lastDate === null) {
                $lastDate = $sign->sign_date;
                $continuous = 1;
            } else {
                $diff = strtotime($lastDate) - strtotime($sign->sign_date);
                if ($diff == 86400) { // 相差1天
                    $continuous++;
                    $lastDate = $sign->sign_date;
                } else {
                    break;
                }
            }
        }

        return $continuous;
    }

    /**
     * 获取用户签到总次数
     */
    public function countUserSigns($userId, $appId = 0): int
    {
        $query = $this->model->newQuery()->where('user_id', $userId);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->count();
    }

    /**
     * 获取签到统计
     */
    public function getSignStats($startDate = null, $endDate = null, $appId = 0)
    {
        $query = $this->query();

        if ($startDate) {
            $query->where('sign_date', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('sign_date', '<=', $endDate);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total_count' => $query->count(),
            'unique_users' => $query->distinct('user_id')->count('user_id'),
            'by_status' => $query->selectRaw('sign_status, COUNT(*) as count')
                ->groupBy('sign_status')
                ->get(),
            'by_date' => $query->selectRaw('sign_date, COUNT(*) as count')
                ->groupBy('sign_date')
                ->orderBy('sign_date', 'desc')
                ->get(),
        ];
    }

    /**
     * 获取用户签到统计
     */
    public function getUserSignStats($userId, $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total_count' => $query->count(),
            'total_points' => $query->sum('sign_points'),
            'continuous_days' => $this->getUserContinuousDays($userId, $appId),
            'by_status' => $query->selectRaw('sign_status, COUNT(*) as count')
                ->groupBy('sign_status')
                ->get(),
        ];
    }
}
