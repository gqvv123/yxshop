<?php

namespace app\repository;

use app\model\NotificationScene;

/**
 * 通知场景仓储类
 */
class NotificationSceneRepository extends BaseRepository
{
    protected $model = NotificationScene::class;

    /**
     * 获取场景列表
     */
    public function getScenes($appId = 0)
    {
        $query = $this->query()
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取活跃场景
     */
    public function getActiveScenes($appId = 0)
    {
        $query = $this->query()
            ->where('is_active', true)
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 根据代码获取场景
     */
    public function getSceneByCode($sceneCode, $appId = 0)
    {
        $query = $this->query()
            ->where('scene_code', $sceneCode)
            ->where('is_active', true);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->first();
    }

    /**
     * 获取场景统计（修复累加 where bug，使用 clone）
     */
    public function getSceneStats($appId = 0)
    {
        $base = $this->query();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
        ];
    }

    /**
     * 列出场景（按 sort 升序），可按 app_id 过滤
     */
    public function listByAppSorted(int $appId = 0)
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }

    /**
     * 仅按 scene_code 查找（不含 is_active 过滤）
     */
    public function findByCode($sceneCode)
    {
        return $this->query()->where('scene_code', $sceneCode)->first();
    }

    /**
     * 按 scene_code + app_id 查找（用于唯一性校验）
     */
    public function findByCodeAndApp($sceneCode, $appId)
    {
        return $this->query()
            ->where('scene_code', $sceneCode)
            ->where('app_id', $appId)
            ->first();
    }

    /**
     * 按 scene_code + app_id 查找，排除指定 id（用于更新时的唯一性校验）
     */
    public function findByCodeAndAppExcept($sceneCode, $appId, $excludeId)
    {
        return $this->query()
            ->where('scene_code', $sceneCode)
            ->where('app_id', $appId)
            ->where('id', '<>', $excludeId)
            ->first();
    }
}
