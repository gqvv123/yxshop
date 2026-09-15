<?php

namespace app\service;

use app\repository\ScheduledTaskLogRepository;
use app\model\ScheduledTaskLog;
use Exception;

/**
 * 定时任务日志服务类
 *
 * @property ScheduledTaskLogRepository $repository
 */
class ScheduledTaskLogService extends BaseService
{
    public function __construct(?ScheduledTaskLogRepository $repository = null)
    {
        parent::__construct($repository ?? new ScheduledTaskLogRepository());
    }

    public function getByTask(int $taskId, int $appId = 0)
    {
        try {
            return $this->repository->getByTask($taskId, $appId);
        } catch (Exception $e) {
            $this->logError('按任务获取日志失败', [
                'task_id' => $taskId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getRecent(int $limit = 50, int $appId = 0)
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

    public function getFailed(int $appId = 0)
    {
        try {
            return $this->repository->getFailed($appId);
        } catch (Exception $e) {
            $this->logError('获取失败日志失败', [
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

    public function logStart(int $taskId, int $appId = 0)
    {
        try {
            $this->logInfo('记录任务开始', ['task_id' => $taskId]);
            return $this->repository->logStart($taskId, $appId);
        } catch (Exception $e) {
            $this->logError('记录任务开始失败', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function logFinish(int $id, int $status, string $result = '', string $errorMessage = '')
    {
        try {
            $this->logInfo('记录任务完成', ['id' => $id, 'status' => $status]);
            return $this->repository->logFinish($id, $status, $result, $errorMessage);
        } catch (Exception $e) {
            $this->logError('记录任务完成失败', [
                'id' => $id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
