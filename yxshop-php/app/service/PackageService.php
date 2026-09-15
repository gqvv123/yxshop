<?php

namespace app\service;

use app\repository\PackageRepository;
use Exception;

/**
 * 套餐服务类
 *
 * @property PackageRepository $repository
 */
class PackageService extends BaseService
{
    public function __construct(?PackageRepository $repository = null)
    {
        parent::__construct($repository ?? new PackageRepository());
    }

    public function getPackages(int $appId = 0): array
    {
        return $this->repository->getPackages($appId);
    }

    public function getActivePackages(int $appId = 0): array
    {
        return $this->repository->getActivePackages($appId);
    }

    public function createPackage(array $data)
    {
        $this->logInfo('创建套餐', ['data' => $data]);
        return $this->repository->create($data);
    }

    public function updatePackage(int $id, array $data)
    {
        $this->logInfo('更新套餐', ['id' => $id]);
        return $this->repository->update($id, $data);
    }

    public function deletePackage(int $id)
    {
        $this->logInfo('删除套餐', ['id' => $id]);
        return $this->repository->softDelete($id);
    }
}
