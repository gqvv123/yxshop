<?php

namespace app\repository;

use app\model\ScheduledTaskLog;

/**
 * 定时任务日志仓储
 */
class ScheduledTaskLogRepository extends BaseRepository
{
    protected $model = ScheduledTaskLog::class;

    /**
     * 按任务获取日志
     */
    public function getByTask(int $taskId, int $appId = 0)
    {
        $query = $this->query()->where('task_id', $taskId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('started_at', 'desc')->get();
    }

    /**
     * 获取最近日志
     */
    public function getRecent(int $limit = 50, int $appId = 0)
    {
        $query = $this->query()->orderBy('started_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['task'])->limit($limit)->get();
    }

    /**
     * 获取失败日志
     */
    public function getFailed(int $appId = 0)
    {
        $query = $this->query()->where('status', ScheduledTaskLog::STATUS_FAILED);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['task'])->orderBy('started_at', 'desc')->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('started_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['task_id'])) {
            $query->where('task_id', $filters['task_id']);
        }
        return $query->with(['task'])->paginate($pageSize);
    }

    /**
     * 记录任务开始
     */
    public function logStart(int $taskId, int $appId = 0): ScheduledTaskLog
    {
        return $this->create([
            'task_id' => $taskId,
            'started_at' => time(),
            'status' => ScheduledTaskLog::STATUS_FAILED, // 初始为失败状态，等待完成时更新
            'app_id' => $appId,
        ]);
    }

    /**
     * 记录任务完成
     */
    public function logFinish(int $id, int $status, string $result = '', string $errorMessage = ''): ScheduledTaskLog
    {
        $log = $this->findOrFail($id);
        $log->finished_at = time();
        $log->status = $status;
        $log->result = $result;
        $log->error_message = $errorMessage;
        $log->save();
        return $log;
    }
}