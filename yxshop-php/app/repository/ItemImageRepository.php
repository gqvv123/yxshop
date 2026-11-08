<?php

namespace app\repository;

use app\model\ItemImage;

/**
 * 商品图片仓储
 */
class ItemImageRepository extends BaseRepository
{
    protected $model = ItemImage::class;

    /**
     * 取某商品所有图片（按 sort 升序）
     */
    public function getByItem(int $itemId)
    {
        return $this->query()
            ->where('item_id', $itemId)
            ->orderBy('sort', 'asc')
            ->get();
    }

    /**
     * 删除某商品所有图片（替换前清空）
     */
    public function deleteByItem(int $itemId): int
    {
        return $this->query()->where('item_id', $itemId)->delete();
    }
}
