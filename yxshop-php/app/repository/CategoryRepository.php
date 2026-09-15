<?php

namespace app\repository;

use app\model\Category;

/**
 * 商品分类仓储类
 */
class CategoryRepository extends BaseRepository
{
    protected $model = Category::class;

    public function __construct()
    {
        parent::__construct(new Category());
    }

    /**
     * 更新分类状态
     */
    public function updateStatus(int $id, int $status): Category
    {
        return $this->update($id, ['status' => $status]);
    }

    /**
     * 取 app 下全部分类（key by id，便于 Service 端做路径回溯）
     */
    public function getAllByApp(int $appId)
    {
        return $this->query()->where('app_id', $appId)->get()->keyBy('id');
    }

    /**
     * 获取分类树
     */
    public function getTree($parentId = 0, $appId = 0)
    {
        $query = $this->query()
            ->where('parent_id', $parentId)
            ->where('is_visible', 1)
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        $categories = $query->get();

        foreach ($categories as $category) {
            $category->children = $this->getTree($category->id, $appId);
        }

        return $categories;
    }

    /**
     * 获取所有父级分类ID
     */
    public function getAllParentIds($categoryId)
    {
        $parentIds = [];
        $category = $this->find($categoryId);
        
        while ($category && $category->parent_id > 0) {
            $parentIds[] = $category->parent_id;
            $category = $this->find($category->parent_id);
        }
        
        return array_reverse($parentIds);
    }

    /**
     * 获取所有子级分类ID
     */
    public function getAllChildIds($categoryId)
    {
        $childIds = [$categoryId];
        $children = $this->query()
            ->where('parent_id', $categoryId)
            ->get();

        foreach ($children as $child) {
            $childIds = array_merge($childIds, $this->getAllChildIds($child->id));
        }

        return $childIds;
    }

    /**
     * 获取分类路径
     */
    public function getCategoryPath($categoryId)
    {
        $path = [];
        $category = $this->find($categoryId);
        
        while ($category) {
            array_unshift($path, $category);
            $category = $category->parent_id > 0 ? $this->find($category->parent_id) : null;
        }
        
        return $path;
    }

    /**
     * 检查是否有子分类
     */
    public function hasChildren($categoryId)
    {
        return $this->query()
            ->where('parent_id', $categoryId)
            ->exists();
    }

    /**
     * 检查是否有商品
     */
    public function hasItems($categoryId)
    {
        return $this->query()
            ->where('id', $categoryId)
            ->whereHas('items')
            ->exists();
    }

    /**
     * app 下所有分类（按 parent/sort 排序，扁平列表）
     */
    public function getListByApp(int $appId = 0)
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('parent_id', 'asc')
            ->orderBy('sort', 'asc')
            ->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedByApp(int $appId, int $pageSize = 20)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->orderBy('parent_id', 'asc')
            ->orderBy('sort', 'asc')
            ->paginate($pageSize);
    }

    /**
     * 最近创建的分类（含商品数）
     */
    public function getRecentWithItemCount(int $appId, int $limit = 20)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->withCount(['items as product_count'])
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 获取分类统计
     */
    public function getCategoryStats($appId = 0)
    {
        $query = $this->query();
        
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => (clone $query)->count(),
            'show' => (clone $query)->where('is_visible', 1)->count(),
            'hide' => (clone $query)->where('is_visible', 0)->count(),
        ];
    }
}
