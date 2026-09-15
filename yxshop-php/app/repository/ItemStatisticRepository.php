<?php

namespace app\repository;

use app\model\ItemStatistic;

/**
 * 商品统计仓储
 */
class ItemStatisticRepository extends BaseRepository
{
    protected $model = ItemStatistic::class;

    /**
     * 按商品获取统计
     */
    public function getByItem(int $itemId, int $appId = 0)
    {
        $query = $this->query()->where('item_id', $itemId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['item'])->orderBy('stat_date', 'desc')->get();
    }

    /**
     * 按日期获取统计
     */
    public function getByDate(int $statDate, int $appId = 0)
    {
        $query = $this->query()->where('stat_date', $statDate);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['item'])->orderBy('pay_count', 'desc')->get();
    }

    /**
     * 获取热门商品
     */
    public function getHotItems(int $statDate, int $limit = 10, int $appId = 0)
    {
        $query = $this->query()
            ->where('stat_date', $statDate)
            ->orderBy('pay_count', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['item'])->limit($limit)->get();
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
        if (!empty($filters['stat_date'])) {
            $query->where('stat_date', (int) $filters['stat_date']);
        }
        if (!empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }
        return $query->with(['item'])->paginate($pageSize);
    }

    /**
     * 获取商品汇总统计
     */
    public function getItemSummary(int $itemId, int $appId = 0): array
    {
        $base = $this->query()->where('item_id', $itemId);
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        return [
            'total_views' => (clone $base)->sum('view_count'),
            'total_favorites' => (clone $base)->sum('favorite_count'),
            'total_carts' => (clone $base)->sum('cart_count'),
            'total_orders' => (clone $base)->sum('order_count'),
            'total_pays' => (clone $base)->sum('pay_count'),
            'total_amount' => (clone $base)->sum('pay_amount'),
        ];
    }
}