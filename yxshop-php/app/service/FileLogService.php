<?php

namespace app\service;

use app\repository\FileLogRepository;

class FileLogService extends BaseService
{
    public function __construct(?FileLogRepository $repository = null)
    {
        parent::__construct($repository ?? new FileLogRepository());
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->getList($appId, $pageSize, $keyword);
    }

    public function getDetail($id)
    {
        return $this->repository->find($id);
    }
}
