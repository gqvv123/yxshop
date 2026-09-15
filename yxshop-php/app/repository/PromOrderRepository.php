<?php

namespace app\repository;

use app\model\PromOrder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * 促销订单仓储类
 */
class PromOrderRepository extends BaseRepository
{
    protected $model = PromOrder::class;

    /**
     * 分页获取促销订单列表（含关联数据）
     */
    public function getPaginatedListWithRelations(int $appId = 0, int $pageSize = 20, string $keyword = '', int $page = 1): LengthAwarePaginator
    {
        $query = $this->query()
            ->with(['promotion:id,title,type', 'order:id,order_no,total_price,status,pay_status', 'user:id,nickname,username'])
            ->orderBy('id', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($keyword !== '') {
            $escapedKeyword = str_replace(['%', '_'], ['\\%', '\\_'], $keyword);
            $query->where(function ($q) use ($escapedKeyword) {
                $q->where('prom_id', $escapedKeyword)
                  ->orWhere('order_id', $escapedKeyword)
                  ->orWhere('prom_type', $escapedKeyword);
            });
        }

        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 获取促销订单列表
     */
    public function getPromOrders($promId = null, $userId = null, $promType = null, $promStatus = null, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->with(['promotion', 'order', 'user'])
            ->orderBy('id', 'desc');

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
            'success_count' => $query->where('prom_status', PromOrder::STATUS_SUCCESS)->count(),
            'success_amount' => $query->where('prom_status', PromOrder::STATUS_SUCCESS)->sum('prom_amount'),
        ];
    }
}
