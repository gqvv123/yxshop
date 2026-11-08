<?php

namespace app\repository;

use app\model\ArticleCategory;

/**
 * 数据仓储：Article Category Repository。
 *
 * @package app\repository
 */
class ArticleCategoryRepository extends BaseRepository
{
    protected $model = ArticleCategory::class;

    public function getTree($parentId = 0, $appId = 0)
    {
        $query = $this->query()
            ->where('parent_id', $parentId)
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
}


