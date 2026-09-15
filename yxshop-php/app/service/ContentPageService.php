<?php

namespace app\service;

use app\repository\ContentPageRepository;

class ContentPageService extends BaseService
{
    public function __construct(?ContentPageRepository $repository = null)
    {
        parent::__construct($repository ?? new ContentPageRepository());
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '', $pageType = '')
    {
        return $this->repository->paginateForList((int) $appId, (int) $pageSize, (string) $keyword, (string) $pageType);
    }
}