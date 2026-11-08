<?php

namespace app\repository;

use app\model\UserPointTask;

/**
 * 用户积分任务仓储
 */
class UserPointTaskRepository extends BaseRepository
{
    protected $model = UserPointTask::class;

    /**
     * 获取用户今日任务记录
     */
    public function getTodayRecord(int $userId, $taskId, int $todayStart, int $todayEnd)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('task_id', $taskId)
            ->whereBetween('completed_at', [$todayStart, $todayEnd])
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * 获取用户任务记录（不限日期）
     */
    public function getByUserAndTask(int $userId, $taskId)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('task_id', $taskId)
            ->orderBy('id', 'desc')
            ->first();
    }

    /**
     * 获取用户已领取的任务
     */
    public function getClaimedByUserAndTask(int $userId, $taskId, int $todayStart = 0, int $todayEnd = 0)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('task_id', $taskId);
        if ($todayStart > 0 && $todayEnd > 0) {
            $query->whereBetween('completed_at', [$todayStart, $todayEnd]);
        }
        return $query->orderBy('id', 'desc')->first();
    }
}
