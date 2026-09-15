<?php

namespace app\service;

use app\repository\ArticleCategoryRepository;

/**
 * 文章分类服务类
 *
 * @property ArticleCategoryRepository $repository
 */
class ArticleCategoryService extends BaseService
{
    public function __construct(?ArticleCategoryRepository $repository = null)
    {
        parent::__construct($repository ?? new ArticleCategoryRepository());
    }

    public function getCategoryTree($appId = 0)
    {
        return $this->repository->getTree(0, $appId);
    }
}


