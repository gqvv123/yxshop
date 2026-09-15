<?php

namespace app\service;

use app\repository\SystemLogRepository;
use app\model\SystemLog;
use Exception;

/**
 * 系统日志服务类
 *
 * @property SystemLogRepository $repository
 */
class SystemLogService extends BaseService
{
    public function __construct(?SystemLogRepository $repository = null)
    {
        parent::__construct($repository ?? new SystemLogRepository(new \app\model\SystemLog()));
    }

    /**
     * 记录系统日志
     */
    public function recordSystemLog($level, $message, $context = null, $file = '', $line = 0, $trace = '', $logType = '', $module = '', $action = '', $ip = '', $userAgent = '', $appId = 0)
    {
        try {
            $log = SystemLog::record($level, $message, $context, $file, $line, $trace, $logType, $module, $action, $ip, $userAgent, $appId);
            return $log;
        } catch (Exception $e) {
            error_log('[SystemLogService] record failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 获取系统日志
     */
    public function getSystemLogs($logLevel = null, $appId = 0, $limit = 20)
    {
        try {
            return $this->repository->getSystemLogs($logLevel, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取系统日志失败', [
                'log_level' => $logLevel,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取日志级别统计
     */
    public function getLogLevelStats($startTime = null, $endTime = null, $appId = 0)
    {
        try {
            return $this->repository->getLogLevelStats($startTime, $endTime, $appId);

        } catch (Exception $e) {
            $this->logError('获取日志级别统计失败', [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取错误日志
     */
    public function getErrorLogs($appId = 0, $limit = 50)
    {
        try {
            return $this->repository->getErrorLogs($appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取错误日志失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 清理过期日志
     */
    public function cleanExpiredLogs($days = 30)
    {
        try {
            $this->logInfo('清理过期系统日志开始', ['days' => $days]);

            $count = $this->repository->cleanExpiredLogs($days);

            $this->logInfo('清理过期系统日志成功', ['count' => $count]);
            return $count;

        } catch (Exception $e) {
            $this->logError('清理过期系统日志失败', [
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getSystemLogList($page, $limit, array $filters = [])
    {
        return $this->repository->paginatedListWithFilters($filters, (int) $page, (int) $limit);
    }

    public function getSystemLogById($id)
    {
        return $this->repository->findOrFail($id);
    }

    public function deleteSystemLog($id)
    {
        return $this->repository->delete($id);
    }

    public function batchDeleteSystemLogs(array $ids)
    {
        return $this->repository->batchDeleteByIds($ids);
    }

    public function clearOldSystemLogs($days)
    {
        return $this->cleanExpiredLogs($days);
    }

    public function exportSystemLogs($startDate, $endDate)
    {
        return $this->repository->exportByDateRange($startDate, $endDate);
    }
}
