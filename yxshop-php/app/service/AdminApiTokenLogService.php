<?php

namespace app\service;

use app\repository\AdminApiTokenLogRepository;
use Exception;

/**
 * 管理后台API Token日志服务类
 *
 * @property AdminApiTokenLogRepository $repository
 */
class AdminApiTokenLogService extends BaseService
{
    public function __construct(?AdminApiTokenLogRepository $repository = null)
    {
        parent::__construct($repository ?? new AdminApiTokenLogRepository());
    }

    public function getByToken(int $tokenId, int $appId = 0)
    {
        try {
            return $this->repository->getByToken($tokenId, $appId);
        } catch (Exception $e) {
            $this->logError('按Token获取日志失败', [
                'token_id' => $tokenId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getRecent(int $limit = 100, int $appId = 0)
    {
        try {
            return $this->repository->getRecent($limit, $appId);
        } catch (Exception $e) {
            $this->logError('获取最近日志失败', [
                'limit' => $limit,
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
            $this->logError('获取日志分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function logRequest(int $tokenId, string $path, string $method, string $ip, int $appId = 0)
    {
        try {
            $this->logInfo('记录API请求开始', [
                'token_id' => $tokenId,
                'path' => $path,
                'method' => $method
            ]);
            return $this->repository->logRequest($tokenId, $path, $method, $ip, $appId);
        } catch (Exception $e) {
            $this->logError('记录API请求失败', [
                'token_id' => $tokenId,
                'path' => $path,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建日志开始', ['data' => $data]);
            $log = $this->repository->create($data);
            $this->logInfo('创建日志成功', ['id' => $log->id]);
            return $log;
        } catch (Exception $e) {
            $this->logError('创建日志失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
