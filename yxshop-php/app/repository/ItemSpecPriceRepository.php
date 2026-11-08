<?php

namespace app\repository;

use app\model\ItemSpecPrice;

/**
 * 商品 SKU 价格库存仓储（yxshop_item_spec_prices）
 */
class ItemSpecPriceRepository extends BaseRepository
{
    protected $model = ItemSpecPrice::class;

    /**
     * 取某商品的所有 SKU
     */
    public function getByItem(int $itemId)
    {
        return $this->query()->where('item_id', $itemId)->get();
    }

    /**
     * 删除某商品的所有 SKU（替换前清空）
     */
    public function deleteByItem(int $itemId): int
    {
        return $this->query()->where('item_id', $itemId)->delete();
    }

    /**
     * 汇总某商品的总库存
     */
    public function sumStockByItem(int $itemId): int
    {
        return (int) $this->query()->where('item_id', $itemId)->sum('store_count');
    }
}
