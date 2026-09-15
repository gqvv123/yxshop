<?php

namespace app\repository;

use app\model\SystemLog;

/**
 * 系统日志仓储类
 */
class SystemLogRepository extends BaseRepository
{
    protected $model = SystemLog::class;

    /**
     * 获取系统日志
     */
    public function getSystemLogs($logLevel = null, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->orderBy('created_at', 'desc');

        if ($logLevel !== null) {
            $query->where('log_level', $logLevel);
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
     * 获取日志级别统计
     */
    public function getLogLevelStats($startTime = null, $endTime = null, $appId = 0)
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

        return $query->selectRaw('log_level, COUNT(*) as count')
            ->groupBy('log_level')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * 获取错误日志
     */
    public function getErrorLogs($appId = 0, $limit = 50)
    {
        $query = $this->query()
            ->whereIn('log_level', [
                SystemLog::LEVEL_WARNING,
                SystemLog::LEVEL_ERROR,
                SystemLog::LEVEL_CRITICAL,
            ])
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 清理过期日志
     */
    public function cleanExpiredLogs($days = 30)
    {
        $expiredTime = time() - ($days * 24 * 3600);

        return $this->query()
            ->where('created_at', '<', $expiredTime)
            ->delete();
    }

    /**
     * 按筛选条件分页查询系统日志列表
     *
     * 支持筛选字段：app_id / level / module
     */
    public function paginatedListWithFilters(array $filters, int $page, int $pageSize)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if (!empty($filters['app_id'])) {
            $query->where('app_id', $filters['app_id']);
        }
        if (!empty($filters['level'])) {
            $query->where('log_level', $filters['level']);
        }
        if (!empty($filters['module'])) {
            $query->where('module', $filters['module']);
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
