<?php

namespace app\repository;

use app\model\NotificationBlacklist;

/**
 * 通知黑名单仓储类
 */
class NotificationBlacklistRepository extends BaseRepository
{
    protected $model = NotificationBlacklist::class;

    /**
     * 获取黑名单列表
     */
    public function getBlacklists($blacklistType = null, $appId = 0)
    {
        $query = $this->query()
            ->orderBy('created_at', 'desc');

        if ($blacklistType !== null) {
            $query->where('blacklist_type', $blacklistType);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取活跃黑名单
     */
    public function getActiveBlacklists($blacklistType = null, $appId = 0)
    {
        $query = $this->query()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc');

        if ($blacklistType !== null) {
            $query->where('blacklist_type', $blacklistType);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 检查是否在黑名单中
     */
    public function isBlacklisted($blacklistType, $blacklistValue, $appId = 0)
    {
        $query = $this->query()
            ->where('blacklist_type', $blacklistType)
            ->where('blacklist_value', $blacklistValue)
            ->where('is_active', true);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->exists();
    }

    /**
     * 获取黑名单统计（修复累加 where bug，使用 clone）
     */
    public function getBlacklistStats($appId = 0)
    {
        $base = $this->query();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
            'by_type' => (clone $base)->selectRaw('blacklist_type, COUNT(*) as count')
                ->groupBy('blacklist_type')
                ->get(),
        ];
    }

    /**
     * 按 app 分页（按 created_at desc）
     */
    public function paginateByApp(int $appId, int $pageSize = 20, int $page = 1)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 校验用户 + 场景 + app 是否已存在黑名单
     */
    public function findByUserSceneApp($userId, $sceneCode, $appId)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('scene_code', $sceneCode)
            ->where('app_id', $appId)
            ->first();
    }

    /**
     * 检查用户 + 场景是否在黑名单中（不限 app）
     */
    public function existsByUserAndScene($userId, $sceneCode): bool
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('scene_code', $sceneCode)
            ->exists();
    }

    /**
     * 按 app 分组统计 by scene_code（用于面板）
     */
    public function statsBySceneCode(int $appId = 0): array
    {
        $base = $this->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total' => (clone $base)->count(),
            'by_scene' => (clone $base)->groupBy('scene_code')
                ->selectRaw('scene_code, count(*) as count')
                ->get()
                ->toArray(),
        ];
    }
}
