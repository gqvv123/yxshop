<?php

namespace app\repository;

use app\model\ItemAttr;

/**
 * 商品属性/规格值仓储（yxshop_item_attrs）
 *
 * 两种历史写法并存：
 *   1) attr_value 只存值，属性名在 yxshop_item_attributes.name（当前写入方式）
 *   2) attr_value 存 "属性名:值"（早期数据）
 * 读取侧统一按 attribute 关系取名称，只在前者缺失时才拆冒号。
 */
class ItemAttrRepository extends BaseRepository
{
    protected $model = ItemAttr::class;

    /**
     * 取某商品所有属性行（带属性定义，避免逐行查名产生 N+1）
     */
    public function getByItem(int $itemId)
    {
        return $this->query()->with(['attribute'])->where('item_id', $itemId)->get();
    }

    /**
     * 删除某商品所有属性行
     */
    public function deleteByItem(int $itemId): int
    {
        return $this->query()->where('item_id', $itemId)->delete();
    }
}
