<?php

namespace app\service;

use app\repository\AdminLogRepository;
use app\model\AdminLog;
use Exception;

/**
 * 管理员日志服务类
 *
 * @property AdminLogRepository $repository
 */
class AdminLogService extends BaseService
{
    public function __construct()
    {
        $this->repository = new AdminLogRepository();
        parent::__construct($this->repository);
    }

    /**
     * 记录管理员日志
     */
    public function recordAdminLog($adminId, $module, $action, $operationType = AdminLog::OP_UPDATE, $targetType = '', $targetId = 0, $targetName = '', $requestUrl = '', $requestMethod = '', $requestParams = [], $responseData = [], $operationResult = AdminLog::RESULT_SUCCESS, $errorMessage = '', $ip = '', $userAgent = '', $appId = 0)
    {
        try {
            $this->logInfo('记录管理员日志开始', [
                'admin_id' => $adminId,
                'action' => $action,
                'app_id' => $appId
            ]);

            $log = AdminLog::record($adminId, $module, $action, $operationType, $targetType, $targetId, $targetName, $requestUrl, $requestMethod, $requestParams, $responseData, $operationResult, $errorMessage, $ip, $userAgent, $appId);

            $this->logInfo('记录管理员日志成功', ['log_id' => $log->id]);
            return $log;

        } catch (Exception $e) {
            $this->logError('记录管理员日志失败', [
                'admin_id' => $adminId,
                'action' => $action,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取管理员日志
     */
    public function getAdminLogs($adminId = null, $action = null, $appId = 0, $limit = 20)
    {
        try {
            return $this->repository->getAdminLogs($adminId, $action, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取管理员日志失败', [
                'admin_id' => $adminId,
                'action' => $action,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取操作统计
     */
    public function getActionStats($startTime = null, $endTime = null, $appId = 0)
    {
        try {
            return $this->repository->getActionStats($startTime, $endTime, $appId);

        } catch (Exception $e) {
            $this->logError('获取操作统计失败', [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取管理员操作统计
     */
    public function getAdminActionStats($startTime = null, $endTime = null, $appId = 0)
    {
        try {
            return $this->repository->getAdminActionStats($startTime, $endTime, $appId);

        } catch (Exception $e) {
            $this->logError('获取管理员操作统计失败', [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 清理过期日志
     */
    public function cleanExpiredLogs($days = 90)
    {
        try {
            $this->logInfo('清理过期管理员日志开始', ['days' => $days]);

            $count = $this->repository->cleanExpiredLogs($days);

            $this->logInfo('清理过期管理员日志成功', ['count' => $count]);
            return $count;

        } catch (Exception $e) {
            $this->logError('清理过期管理员日志失败', [
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getAdminLogList($page, $limit, array $filters = [])
    {
        return $this->repository->paginatedListWithFilters($filters, (int) $page, (int) $limit);
    }

    public function getAdminLogById($id)
    {
        return $this->repository->findOrFail($id);
    }

    public function deleteAdminLog($id)
    {
        return $this->repository->delete($id);
    }

    public function batchDeleteAdminLogs(array $ids)
    {
        return $this->repository->batchDeleteByIds($ids);
    }

    public function clearOldAdminLogs($days)
    {
        return $this->cleanExpiredLogs($days);
    }

    public function exportAdminLogs($startDate, $endDate)
    {
        return $this->repository->exportByDateRange($startDate, $endDate);
    }
}
