<?php

namespace app\service;

use app\repository\UploadGroupRepository;
use app\model\UploadGroup;

class UploadGroupService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new UploadGroupRepository());
    }

    public function getGroupList(int $appId = 0)
    {
        return $this->repository->listGroups($appId);
    }

    public function getGroupById(int $id)
    {
        return $this->repository->find($id);
    }

    public function createGroup(array $data)
    {
        return $this->repository->create($data);
    }

    public function updateGroup(int $id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function deleteGroup(int $id)
    {
        return $this->repository->softDelete($id);
    }
}
