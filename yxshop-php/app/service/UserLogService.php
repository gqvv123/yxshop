<?php

namespace app\service;

use app\repository\UserLogRepository;
use app\model\UserLog;
use Exception;

/**
 * 用户日志服务类
 *
 * @property UserLogRepository $repository
 */
class UserLogService extends BaseService
{
    public function __construct(?UserLogRepository $repository = null)
    {
        parent::__construct($repository ?? new UserLogRepository(new \app\model\UserLog()));
    }

    /**
     * 记录用户日志
     */
    public function recordUserLog($userId, $module, $action, $operationType = UserLog::OP_VIEW, $targetType = '', $targetId = 0, $operationDesc = '', $requestUrl = '', $requestMethod = '', $operationResult = UserLog::RESULT_SUCCESS, $errorMessage = '', $ip = '', $userAgent = '', $appId = 0)
    {
        try {
            $this->logInfo('记录用户日志开始', [
                'user_id' => $userId,
                'action' => $action,
                'app_id' => $appId
            ]);

            $log = UserLog::record($userId, $module, $action, $operationType, $targetType, $targetId, $operationDesc, $requestUrl, $requestMethod, $operationResult, $errorMessage, $ip, $userAgent, $appId);

            $this->logInfo('记录用户日志成功', ['log_id' => $log->id]);
            return $log;

        } catch (Exception $e) {
            $this->logError('记录用户日志失败', [
                'user_id' => $userId,
                'action' => $action,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户日志
     */
    public function getUserLogs($userId = null, $action = null, $appId = 0, $limit = 20)
    {
        try {
            return $this->repository->getUserLogs($userId, $action, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取用户日志失败', [
                'user_id' => $userId,
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
     * 获取用户操作统计
     */
    public function getUserActionStats($startTime = null, $endTime = null, $appId = 0)
    {
        try {
            return $this->repository->getUserActionStats($startTime, $endTime, $appId);

        } catch (Exception $e) {
            $this->logError('获取用户操作统计失败', [
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
            $this->logInfo('清理过期用户日志开始', ['days' => $days]);

            $count = $this->repository->cleanExpiredLogs($days);

            $this->logInfo('清理过期用户日志成功', ['count' => $count]);
            return $count;

        } catch (Exception $e) {
            $this->logError('清理过期用户日志失败', [
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getUserLogList($page, $limit, array $filters = [])
    {
        return $this->repository->paginatedListWithFilters($filters, (int) $page, (int) $limit);
    }

    public function getUserLogById($id)
    {
        return $this->repository->findOrFail($id);
    }

    public function deleteUserLog($id)
    {
        return $this->repository->delete($id);
    }

    public function batchDeleteUserLogs(array $ids)
    {
        return $this->repository->batchDeleteByIds($ids);
    }

    public function clearOldUserLogs($days)
    {
        return $this->cleanExpiredLogs($days);
    }

    public function exportUserLogs($startDate, $endDate)
    {
        return $this->repository->exportByDateRange($startDate, $endDate);
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        if ($keyword !== '' && $keyword !== null) {
            return $this->repository->paginatedListWithFilters(
                ['app_id' => $appId > 0 ? $appId : null, 'action' => $keyword],
                1,
                (int) $pageSize
            );
        }
        return $this->repository->paginatedByApp((int) $appId, (int) $pageSize, 'created_at', 'desc');
    }
}
