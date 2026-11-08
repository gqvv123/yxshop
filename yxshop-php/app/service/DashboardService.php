<?php

namespace app\service;

use app\repository\OrderRepository;
use app\repository\UserRepository;

class DashboardService
{
    protected OrderRepository $orderRepository;
    protected UserRepository $userRepository;

    public function __construct()
    {
        $this->orderRepository = new OrderRepository();
        $this->userRepository = new UserRepository();
    }

    public function getTrafficSources(int $appId): array
    {
        try {
            $query = $this->orderRepository->query();
            if ($appId > 0) {
                $query->where('app_id', $appId);
            }

            $total = (clone $query)->count();
            if ($total === 0) {
                return [];
            }

            // source 为 tinyint，使用 Order 模型常量
            $miniprogram = (clone $query)->where('source', \app\model\Order::ORDER_SOURCE_MINIPROGRAM)->count();
            $h5          = (clone $query)->where('source', \app\model\Order::ORDER_SOURCE_H5)->count();
            $app         = (clone $query)->where('source', \app\model\Order::ORDER_SOURCE_APP)->count();
            $other       = $total - $miniprogram - $h5 - $app;

            return [
                ['label' => '小程序', 'value' => $miniprogram, 'percent' => round($miniprogram / $total * 100, 1)],
                ['label' => 'H5',    'value' => $h5,          'percent' => round($h5 / $total * 100, 1)],
                ['label' => 'APP',   'value' => $app,         'percent' => round($app / $total * 100, 1)],
                ['label' => '其他',   'value' => $other,       'percent' => round($other / $total * 100, 1)],
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getGenderRatio(int $appId): array
    {
        try {
            $query = $this->userRepository->query();
            if ($appId > 0) {
                $query->where('app_id', $appId);
            }

            $male = (clone $query)->where('gender', 1)->count();
            $female = (clone $query)->where('gender', 2)->count();
            $unknown = (clone $query)->whereNotIn('gender', [1, 2])->count();

            $total = $male + $female + $unknown;
            if ($total === 0) {
                return ['male' => 0, 'female' => 0, 'unknown' => 0];
            }

            return [
                'male' => round($male / $total * 100, 1),
                'female' => round($female / $total * 100, 1),
                'unknown' => round($unknown / $total * 100, 1),
            ];
        } catch (\Throwable $e) {
            return ['male' => 0, 'female' => 0, 'unknown' => 0];
        }
    }

    public function getRegionData(int $appId): array
    {
        try {
            $query = $this->userRepository->query()
                ->whereNotNull('province')
                ->where('province', '!=', '');

            if ($appId > 0) {
                $query->where('app_id', $appId);
            }

            $rows = $query->selectRaw('province as name, count(*) as value')
                ->groupBy('province')
                ->orderByDesc('value')
                ->limit(10)
                ->get()
                ->toArray();

            return $rows;
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function getCustomerTrend(int $appId): array
    {
        try {
            $result = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-{$i} days"));
                $start = strtotime($date . ' 00:00:00');
                $end = strtotime($date . ' 23:59:59');

                $query = $this->userRepository->query()
                    ->whereBetween('created_at', [$start, $end]);

                if ($appId > 0) {
                    $query->where('app_id', $appId);
                }

                $result[] = [
                    'date' => $date,
                    'count' => $query->count(),
                ];
            }

            return $result;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * 获取复购用户数（当前周期）
     */
    public function getReturningUsers(int $appId): int
    {
        try {
            $query = $this->orderRepository->query()
                ->where('app_id', $appId)
                ->where('status', '!=', 20)
                ->selectRaw('user_id, count(*) as cnt')
                ->groupBy('user_id')
                ->havingRaw('cnt > 1');

            return $query->get()->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /**
     * 获取复购用户数（上一周期，用于计算趋势）
     */
    public function getReturningUsersPrevPeriod(int $appId): int
    {
        try {
            $prevPeriodStart = strtotime('-14 days');
            $prevPeriodEnd = strtotime('-7 days');

            $query = $this->orderRepository->query()
                ->where('app_id', $appId)
                ->where('status', '!=', 20)
                ->whereBetween('created_at', [$prevPeriodStart, $prevPeriodEnd])
                ->selectRaw('user_id, count(*) as cnt')
                ->groupBy('user_id')
                ->havingRaw('cnt > 1');

            return $query->get()->count();
        } catch (\Throwable $e) {
            return 0;
        }
    }
}
