<?php

namespace app\repository;

use app\model\AdminPasswordHistory;

/**
 * 管理员密码历史仓储类
 */
class AdminPasswordHistoryRepository extends BaseRepository
{
    protected $model = AdminPasswordHistory::class;

    /**
     * 获取管理员的密码历史（按时间倒序）
     */
    public function getRecentByAdmin(int $adminId, int $limit): array
    {
        return $this->query()
            ->where('admin_id', $adminId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->all();
    }

    /**
     * 统计管理员密码历史数量
     */
    public function countByAdmin(int $adminId): int
    {
        return $this->query()->where('admin_id', $adminId)->count();
    }

    /**
     * 获取超出保留数量的旧记录ID
     */
    public function getExcessOldIds(int $adminId, int $keepCount): array
    {
        $total = $this->countByAdmin($adminId);
        if ($total <= $keepCount) {
            return [];
        }

        return $this->query()
            ->where('admin_id', $adminId)
            ->orderBy('created_at', 'desc')
            ->skip($keepCount)
            ->take($total - $keepCount)
            ->pluck('id')
            ->all();
    }

    /**
     * 按ID批量删除
     */
    public function deleteByIds(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        return $this->query()->whereIn('id', $ids)->delete();
    }
}
