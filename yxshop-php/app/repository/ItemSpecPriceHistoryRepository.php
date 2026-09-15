<?php

namespace app\repository;

use app\model\ItemSpecPriceHistory;

/**
 * 商品规格价格历史仓储类
 */
class ItemSpecPriceHistoryRepository extends BaseRepository
{
    protected $model = ItemSpecPriceHistory::class;

    /**
     * 获取商品的价格变动历史
     */
    public function getByItemId(int $itemId, int $limit = 100)
    {
        return $this->query()
            ->where('item_id', $itemId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取商品某规格的价格变动历史
     */
    public function getBySpecId(int $itemId, int $specId, int $limit = 100)
    {
        return $this->query()
            ->where('item_id', $itemId)
            ->where('spec_id', $specId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 记录价格变动
     */
    public function createLog(array $data): ItemSpecPriceHistory
    {
        $now = time();
        $data['created_at'] = $data['created_at'] ?? $now;
        $data['updated_at'] = $data['updated_at'] ?? $now;
        $data['spec_id']    = $data['spec_id'] ?? 0;

        return $this->query()->create($data);
    }
}
