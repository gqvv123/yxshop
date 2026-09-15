<?php

namespace app\repository;

use app\model\ItemTagRelation;

/**
 * 商品标签关联仓储类
 */
class ItemTagRelationRepository extends BaseRepository
{
    protected $model = ItemTagRelation::class;

    /**
     * 获取商品标签
     */
    public function getItemTags($itemId)
    {
        return $this->query()
            ->where('item_id', $itemId)
            ->with(['tag'])
            ->get();
    }

    /**
     * 获取标签商品
     */
    public function getTagItems($tagId, $appId = 0)
    {
        $query = $this->query()
            ->where('tag_id', $tagId)
            ->with(['item']);

        if ($appId > 0) {
            $query->whereHas('item', function ($q) use ($appId) {
                $q->where('app_id', $appId);
            });
        }

        return $query->get();
    }

    /**
     * 添加商品标签
     */
    public function addItemTag($itemId, $tagId)
    {
        $existing = $this->query()
            ->where('item_id', $itemId)
            ->where('tag_id', $tagId)
            ->first();

        if (!$existing) {
            return $this->create([
                'item_id' => $itemId,
                'tag_id' => $tagId,
            ]);
        }

        return $existing;
    }

    /**
     * 移除商品标签
     */
    public function removeItemTag($itemId, $tagId)
    {
        return $this->query()
            ->where('item_id', $itemId)
            ->where('tag_id', $tagId)
            ->delete();
    }

    /**
     * 批量设置商品标签（替换式：先全部删除，再批量插入）
     */
    public function setItemTags($itemId, array $tagIds, $appId = 0)
    {
        $this->query()->where('item_id', $itemId)->delete();

        if (empty($tagIds)) {
            return true;
        }

        $now = time();
        $data = [];
        foreach ($tagIds as $tagId) {
            $data[] = [
                'item_id'    => $itemId,
                'tag_id'     => $tagId,
                'app_id'     => $appId,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        return $this->createMany($data);
    }

    /**
     * 删除某商品所有标签关联（替换前清空，由 Service 调用）
     */
    public function deleteByItem(int $itemId): int
    {
        return $this->query()->where('item_id', $itemId)->delete();
    }

    /**
     * 获取商品标签统计
     */
    public function getItemTagStats($itemId)
    {
        return $this->query()
            ->where('item_id', $itemId)
            ->count();
    }
}
