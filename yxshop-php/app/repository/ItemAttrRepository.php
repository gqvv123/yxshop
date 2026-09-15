<?php

namespace app\repository;

use app\model\ItemAttr;

/**
 * 商品属性/规格值仓储（yxshop_item_attrs，存 attr_value="规格名:值" 平铺记录）
 */
class ItemAttrRepository extends BaseRepository
{
    protected $model = ItemAttr::class;

    /**
     * 取某商品所有属性行
     */
    public function getByItem(int $itemId)
    {
        return $this->query()->where('item_id', $itemId)->get();
    }

    /**
     * 删除某商品所有属性行
     */
    public function deleteByItem(int $itemId): int
    {
        return $this->query()->where('item_id', $itemId)->delete();
    }
}
