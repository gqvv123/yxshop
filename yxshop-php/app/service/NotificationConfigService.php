<?php

namespace app\service;

use app\repository\NotificationConfigRepository;
use app\model\NotificationConfig;
use Exception;

class NotificationConfigService extends BaseService
{
    public function __construct(?NotificationConfigRepository $repository = null)
    {
        parent::__construct($repository ?? new NotificationConfigRepository());
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->getList($appId, $pageSize, $keyword);
    }

    public function getDetail($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->softDelete($id);
    }
}
