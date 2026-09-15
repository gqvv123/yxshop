<?php

namespace app\repository;

use app\model\Promotion;

/**
 * 促销订单仓储类
 */
class PromotionRepository extends BaseRepository
{
    protected $model = Promotion::class;

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if ($keyword) {
            $query->where('name', 'like', '%' . $keyword . '%');
        }
        return $query->paginate($pageSize);
    }

    /**
     * 获取促销订单列表
     */
    public function getPromOrders($promId = null, $userId = null, $promType = null, $promStatus = null, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->with(['promotion', 'order', 'user'])
            ->orderBy('created_at', 'desc');

        if ($promId !== null) {
            $query->where('prom_id', $promId);
        }

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($promType !== null) {
            $query->where('prom_type', $promType);
        }

        if ($promStatus !== null) {
            $query->where('prom_status', $promStatus);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 获取促销订单统计
     */
    public function getPromOrderStats($startTime = null, $endTime = null, $appId = 0)
    {
        $query = $this->query();

        if ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }

        if ($endTime) {
            $query->where('created_at', '<=', $endTime);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('prom_amount'),
            'by_type' => $query->selectRaw('prom_type, COUNT(*) as count, SUM(prom_amount) as total_amount')
                ->groupBy('prom_type')
                ->get(),
            'by_status' => $query->selectRaw('prom_status, COUNT(*) as count, SUM(prom_amount) as total_amount')
                ->groupBy('prom_status')
                ->get(),
        ];
    }

    /**
     * 获取用户促销订单统计
     */
    public function getUserPromOrderStats($userId, $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total_count' => $query->count(),
            'total_amount' => $query->sum('prom_amount'),
            'success_count' => $query->where('prom_status', Promotion::STATUS_SUCCESS)->count(),
            'success_amount' => $query->where('prom_status', Promotion::STATUS_SUCCESS)->sum('prom_amount'),
        ];
    }
}
