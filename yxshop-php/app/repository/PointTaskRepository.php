<?php

namespace app\repository;

use app\model\PointTask;
use app\model\UserPointTask;

/**
 * 积分任务仓储
 */
class PointTaskRepository extends BaseRepository
{
    protected $model = PointTask::class;

    /**
     * 获取启用的任务列表
     */
    public function getActiveTasks(int $appId = 0)
    {
        $query = $this->query()
            ->where('status', 1)
            ->when($appId > 0, function ($q) use ($appId) {
                $q->where(function ($query) use ($appId) {
                    $query->where('app_id', $appId)->orWhere('app_id', 0);
                });
            })
            ->orderBy('sort', 'desc')
            ->orderBy('id', 'asc');
        return $query->get();
    }

    /**
     * 获取启用的任务
     */
    public function findActive($id)
    {
        return $this->query()->where('id', $id)->where('status', 1)->first();
    }
}
