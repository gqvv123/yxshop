<?php

namespace app\service;

use app\repository\EmailLogRepository;
use app\model\EmailLog;

class EmailLogService extends BaseService
{
    public function __construct(?EmailLogRepository $repository = null)
    {
        $repository = $repository ?? new EmailLogRepository();
        parent::__construct($repository);
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
