<?php

namespace app\repository;

use app\model\ItemSpecStockLog;

/**
 * 商品标签仓储类
 */
class ItemSpecStockLogRepository extends BaseRepository
{
    protected $model = ItemSpecStockLog::class;

    /**
     * 获取标签列表
     */
    public function getTags($appId = 0)
    {
        $query = $this->query()
            ->where('is_show', 1)
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取热门标签
     */
    public function getHotTags($appId = 0, $limit = 10)
    {
        $query = $this->query()
            ->where('is_show', 1)
            ->withCount('items')
            ->having('items_count', '>', 0)
            ->orderBy('items_count', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * 搜索标签
     */
    public function searchTags($keyword, $appId = 0)
    {
        $query = $this->query()
            ->where('is_show', 1)
            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取标签统计
     */
    public function getTagStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'show' => $query->where('is_show', 1)->count(),
            'hide' => $query->where('is_show', 0)->count(),
        ];
    }
}
