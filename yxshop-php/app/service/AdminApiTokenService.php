<?php

namespace app\service;

use app\repository\AdminApiTokenRepository;
use app\model\AdminApiToken;
use Exception;

/**
 * 管理后台API Token服务类
 *
 * @property AdminApiTokenRepository $repository
 */
class AdminApiTokenService extends BaseService
{
    // 暴露状态常量供 Controller 层使用，避免 Controller 直接引用 Model
    const STATUS_DISABLED = AdminApiToken::STATUS_DISABLED;
    const STATUS_ENABLED  = AdminApiToken::STATUS_ENABLED;

    public function __construct(?AdminApiTokenRepository $repository = null)
    {
        parent::__construct($repository ?? new AdminApiTokenRepository());
    }

    public function getByAdmin(int $adminId, int $appId = 0)
    {
        try {
            return $this->repository->getByAdmin($adminId, $appId);
        } catch (Exception $e) {
            $this->logError('按管理员获取Token失败', [
                'admin_id' => $adminId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function findByTokenHash(string $tokenHash, int $appId = 0)
    {
        try {
            return $this->repository->findByTokenHash($tokenHash, $appId);
        } catch (Exception $e) {
            $this->logError('按Token哈希查找失败', [
                'token_hash' => $tokenHash,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getEnabled(int $appId = 0)
    {
        try {
            return $this->repository->getEnabled($appId);
        } catch (Exception $e) {
            $this->logError('获取已启用Token失败', [
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
            $this->logError('获取Token分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateLastUsedAt(int $id)
    {
        try {
            $this->logInfo('更新Token最后使用时间开始', ['id' => $id]);
            $token = $this->repository->updateLastUsedAt($id);
            $this->logInfo('更新Token最后使用时间成功', ['id' => $id]);
            return $token;
        } catch (Exception $e) {
            $this->logError('更新Token最后使用时间失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function disable(int $id)
    {
        try {
            $this->logInfo('禁用Token开始', ['id' => $id]);
            $token = $this->repository->disable($id);
            $this->logInfo('禁用Token成功', ['id' => $id]);
            return $token;
        } catch (Exception $e) {
            $this->logError('禁用Token失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function cleanExpired(int $appId = 0)
    {
        try {
            $this->logInfo('清理过期Token开始', ['app_id' => $appId]);
            $count = $this->repository->cleanExpired($appId);
            $this->logInfo('清理过期Token成功', ['count' => $count]);
            return $count;
        } catch (Exception $e) {
            $this->logError('清理过期Token失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建Token开始', ['data' => $data]);
            $token = $this->repository->create($data);
            $this->logInfo('创建Token成功', ['id' => $token->id]);
            return $token;
        } catch (Exception $e) {
            $this->logError('创建Token失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function delete($id)
    {
        try {
            $this->logInfo('删除Token开始', ['id' => $id]);
            $result = $this->repository->delete($id);
            $this->logInfo('删除Token成功', ['id' => $id]);
            return $result;
        } catch (Exception $e) {
            $this->logError('删除Token失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
