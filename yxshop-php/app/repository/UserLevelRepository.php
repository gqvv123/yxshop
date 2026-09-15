<?php

namespace app\repository;

use app\model\UserLevel;

/**
 * 用户等级仓储类
 */
class UserLevelRepository extends BaseRepository
{
    protected $model = UserLevel::class;

    /**
     * 获取等级列表
     */
    public function getLevels($appId = 0)
    {
        $query = $this->query()
            ->orderBy('level', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取默认等级
     */
    public function getDefaultLevel($appId = 0)
    {
        $query = $this->query()->where('is_default', 1);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->first();
    }

    /**
     * 根据等级值获取等级
     */
    public function getByLevel($level, $appId = 0)
    {
        $query = $this->query()->where('level', $level);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->first();
    }

    /**
     * 获取下一个更高等级
     */
    public function getNextLevel(int $appId, int $currentLevel): ?UserLevel
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('level', '>', $currentLevel)
            ->where('status', 1)
            ->orderBy('level', 'asc')
            ->first();
    }

    /**
     * 获取等级统计
     */
    public function getLevelStats($appId = 0)
    {
        $base = $this->model->newQuery();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total'   => (clone $base)->count(),
            'default' => (clone $base)->where('is_default', 1)->count(),
        ];
    }

    /**
     * 清除指定 app 下的默认等级标记
     *
     * @param int      $appId
     * @param int|null $excludeId  排除指定等级 ID（用于更新时保留自身）
     */
    public function clearDefaultForApp(int $appId, $excludeId = null): int
    {
        $query = $this->model->newQuery()
            ->where('app_id', $appId)
            ->where('is_default', 1);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->update(['is_default' => 0]);
    }

    /**
     * 按 app 分页查询等级列表
     */
    public function paginateForList(int $appId = 0, int $pageSize = 20)
    {
        $query = $this->model->newQuery()->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->paginate($pageSize);
    }
}
