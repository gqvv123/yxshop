<?php

namespace app\repository;

use app\model\ItemSpecItem;

/**
 * 商品规格项仓储类
 */
class ItemSpecItemRepository extends BaseRepository
{
    protected $model = ItemSpecItem::class;

    /**
     * 获取规格项列表
     */
    public function getItems($appId = 0)
    {
        $query = $this->query()->orderBy('order', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取热门规格项
     */
    public function getHotItems($appId = 0, $limit = 10)
    {
        $query = $this->query()
            ->withCount('items')
            ->having('items_count', '>', 0)
            ->orderBy('items_count', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * 搜索规格项
     */
    public function searchItems($keyword, $appId = 0)
    {
        $query = $this->query()
            ->where('item', 'like', '%' . $keyword . '%')
            ->orderBy('order', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取规格项统计
     */
    public function getItemStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
        ];
    }
}
