<?php

namespace app\repository;

use app\model\ItemTag;

/**
 * 商品标签仓储类
 */
class ItemTagRepository extends BaseRepository
{
    protected $model = ItemTag::class;

    /**
     * 获取标签列表
     */
    public function getTags($appId = 0)
    {
        $query = $this->query()
            ->where('status', 1)
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    public function getHotTags($appId = 0, $limit = 10)
    {
        $query = $this->query()
            ->where('status', 1)
            ->withCount('items')
            ->having('items_count', '>', 0)
            ->orderBy('items_count', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->limit($limit)->get();
    }

    public function searchTags($keyword, $appId = 0)
    {
        $query = $this->query()
            ->where('status', 1)
            ->where('tag_name', 'like', '%' . $keyword . '%')
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    public function getTagStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'show' => $query->where('status', 1)->count(),
            'hide' => $query->where('status', 0)->count(),
        ];
    }

    /**
     * 按 app + tag_name 查找一个（创建前查重用）
     */
    public function findByNameAndApp(string $tagName, $appId, $excludeId = null)
    {
        $query = $this->model->newQuery()
            ->where('tag_name', $tagName)
            ->where('app_id', $appId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->first();
    }

    /**
     * 后台分页（支持 tag_name 关键词）
     */
    public function paginatedForAdmin(int $appId = 0, int $pageSize = 20, string $keyword = '')
    {
        $query = $this->model->newQuery()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if ($keyword !== '') {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $keyword);
            $query->where('tag_name', 'like', '%' . $escaped . '%');
        }
        return $query->paginate($pageSize);
    }

}
