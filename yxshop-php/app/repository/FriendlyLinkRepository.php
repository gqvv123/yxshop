<?php

namespace app\repository;

use app\model\FriendlyLink;

/**
 * 友情链接仓储
 */
class FriendlyLinkRepository extends BaseRepository
{
    protected $model = FriendlyLink::class;

    /**
     * 获取已启用的链接
     */
    public function getEnabled(int $appId = 0)
    {
        $query = $this->query()->where('status', FriendlyLink::STATUS_ENABLED);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('sort', 'asc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['keyword'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['keyword']);
            $query->where('link_name', 'like', '%' . $escaped . '%');
        }
        return $query->paginate($pageSize);
    }

    /**
     * 获取链接统计
     */
    public function getStats(array $conditions = [])
    {
        $appId = $conditions['app_id'] ?? 0;
        $base = $this->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        return [
            'total' => (clone $base)->count(),
            'enabled' => (clone $base)->where('status', FriendlyLink::STATUS_ENABLED)->count(),
            'disabled' => (clone $base)->where('status', FriendlyLink::STATUS_DISABLED)->count(),
        ];
    }
}