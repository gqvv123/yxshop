<?php

namespace app\repository;

use app\model\AdminLog;

/**
 * 管理员日志仓储类
 */
class AdminLogRepository extends BaseRepository
{
    protected $model = AdminLog::class;

    /**
     * 获取管理员日志
     */
    public function getAdminLogs($adminId = null, $action = null, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->with(['admin'])
            ->orderBy('created_at', 'desc');

        if ($adminId !== null) {
            $query->where('admin_id', $adminId);
        }

        if ($action !== null) {
            $query->where('action', $action);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 获取操作统计
     */
    public function getActionStats($startTime = null, $endTime = null, $appId = 0)
    {
        $query = $this->query();

        if ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }

        if ($endTime) {
            $query->where('created_at', '<=', $endTime);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * 获取管理员操作统计
     */
    public function getAdminActionStats($startTime = null, $endTime = null, $appId = 0)
    {
        $query = $this->query();

        if ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }

        if ($endTime) {
            $query->where('created_at', '<=', $endTime);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->selectRaw('admin_id, admin_name, COUNT(*) as count')
            ->groupBy('admin_id', 'admin_name')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * 清理过期日志
     */
    public function cleanExpiredLogs($days = 90)
    {
        $expiredTime = time() - ($days * 24 * 3600);

        return $this->query()
            ->where('created_at', '<', $expiredTime)
            ->delete();
    }

    /**
     * 按筛选条件分页查询管理员日志列表
     *
     * 支持筛选字段：app_id / admin_id / action(模糊)
     */
    public function paginatedListWithFilters(array $filters, int $page, int $pageSize)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if (!empty($filters['app_id'])) {
            $query->where('app_id', $filters['app_id']);
        }
        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', 'like', '%' . $filters['action'] . '%');
        }
        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 按 ID 集合批量删除
     */
    public function batchDeleteByIds(array $ids): int
    {
        if (empty($ids)) {
            return 0;
        }
        return (int) $this->deleteWhere(['id' => $ids]);
    }

    /**
     * 按日期范围导出
     */
    public function exportByDateRange(?string $startDate, ?string $endDate)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if (!empty($startDate)) {
            $query->where('created_at', '>=', strtotime($startDate));
        }
        if (!empty($endDate)) {
            $query->where('created_at', '<=', strtotime($endDate . ' 23:59:59'));
        }
        return $query->get();
    }
}
