<?php

namespace app\service;

use app\repository\RechargePackageRepository;
use app\model\RechargePackage;
use Exception;

/**
 * 充值套餐服务类
 *
 * @property RechargePackageRepository $repository
 */
class RechargePackageService extends BaseService
{
    public function __construct(?RechargePackageRepository $repository = null)
    {
        parent::__construct($repository ?? new RechargePackageRepository());
    }

    public function getEnabled(int $appId = 0)
    {
        try {
            return $this->repository->getEnabled($appId);
        } catch (Exception $e) {
            $this->logError('获取已启用套餐失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        try {
            return $this->repository->getPaginatedList($appId, $filters, $pageSize);
        } catch (Exception $e) {
            $this->logError('获取套餐分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getStats(array $conditions = [])
    {
        try {
            return $this->repository->getStats($conditions);
        } catch (Exception $e) {
            $this->logError('获取套餐统计失败', [
                'app_id' => $conditions['app_id'] ?? 0,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function enable(int $id)
    {
        try {
            $this->logInfo('启用套餐开始', ['id' => $id]);
            $package = $this->repository->findOrFail($id);
            $package->status = RechargePackage::STATUS_ENABLED;
            $package->save();
            $this->logInfo('启用套餐成功', ['id' => $id]);
            return $package;
        } catch (Exception $e) {
            $this->logError('启用套餐失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function disable(int $id)
    {
        try {
            $this->logInfo('禁用套餐开始', ['id' => $id]);
            $package = $this->repository->findOrFail($id);
            $package->status = RechargePackage::STATUS_DISABLED;
            $package->save();
            $this->logInfo('禁用套餐成功', ['id' => $id]);
            return $package;
        } catch (Exception $e) {
            $this->logError('禁用套餐失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建套餐开始', ['data' => $data]);
            $package = $this->repository->create($data);
            $this->logInfo('创建套餐成功', ['id' => $package->id]);
            return $package;
        } catch (Exception $e) {
            $this->logError('创建套餐失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新套餐开始', ['id' => $id, 'data' => $data]);
            $package = $this->repository->update($id, $data);
            $this->logInfo('更新套餐成功', ['id' => $id]);
            return $package;
        } catch (Exception $e) {
            $this->logError('更新套餐失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $this->logInfo('删除套餐开始', ['id' => $id]);
            $result = $this->repository->delete($id);
            $this->logInfo('删除套餐成功', ['id' => $id]);
            return $result;
        } catch (Exception $e) {
            $this->logError('删除套餐失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
