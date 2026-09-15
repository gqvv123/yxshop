<?php

namespace app\service;

use app\repository\ScheduledTaskRepository;
use app\model\ScheduledTask;
use Exception;

/**
 * 定时任务服务类
 *
 * @property ScheduledTaskRepository $repository
 */
class ScheduledTaskService extends BaseService
{
    public function __construct(?ScheduledTaskRepository $repository = null)
    {
        parent::__construct($repository ?? new ScheduledTaskRepository());
    }

    public function getEnabled(int $appId = 0)
    {
        try {
            return $this->repository->getEnabled($appId);
        } catch (Exception $e) {
            $this->logError('获取已启用任务失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByType(string $taskType, int $appId = 0)
    {
        try {
            return $this->repository->getByType($taskType, $appId);
        } catch (Exception $e) {
            $this->logError('按类型获取任务失败', [
                'task_type' => $taskType,
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
            $this->logError('获取任务分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateLastRun(int $id, int $status)
    {
        try {
            $this->logInfo('更新任务最后运行状态开始', ['id' => $id, 'status' => $status]);
            $task = $this->repository->updateLastRun($id, $status);
            $this->logInfo('更新任务最后运行状态成功', ['id' => $id]);
            return $task;
        } catch (Exception $e) {
            $this->logError('更新任务最后运行状态失败', [
                'id' => $id,
                'status' => $status,
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
            $this->logError('获取任务统计失败', [
                'app_id' => $conditions['app_id'] ?? 0,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function enable(int $id)
    {
        try {
            $this->logInfo('启用任务开始', ['id' => $id]);
            $task = $this->repository->findOrFail($id);
            $task->status = ScheduledTask::STATUS_ENABLED;
            $task->save();
            $this->logInfo('启用任务成功', ['id' => $id]);
            return $task;
        } catch (Exception $e) {
            $this->logError('启用任务失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function disable(int $id)
    {
        try {
            $this->logInfo('禁用任务开始', ['id' => $id]);
            $task = $this->repository->findOrFail($id);
            $task->status = ScheduledTask::STATUS_DISABLED;
            $task->save();
            $this->logInfo('禁用任务成功', ['id' => $id]);
            return $task;
        } catch (Exception $e) {
            $this->logError('禁用任务失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建任务开始', ['data' => $data]);
            $task = $this->repository->create($data);
            $this->logInfo('创建任务成功', ['id' => $task->id]);
            return $task;
        } catch (Exception $e) {
            $this->logError('创建任务失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新任务开始', ['id' => $id, 'data' => $data]);
            $task = $this->repository->update($id, $data);
            $this->logInfo('更新任务成功', ['id' => $id]);
            return $task;
        } catch (Exception $e) {
            $this->logError('更新任务失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
