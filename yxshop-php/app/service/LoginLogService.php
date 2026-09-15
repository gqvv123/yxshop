<?php

namespace app\service;

use app\repository\LoginLogRepository;
use app\model\LoginLog;
use Exception;

/**
 * 登录日志服务类
 *
 * @property LoginLogRepository $repository
 */
class LoginLogService extends BaseService
{
    // 暴露结果常量供 Controller 层使用，避免 Controller 直接引用 Model
    const RESULT_SUCCESS = LoginLog::RESULT_SUCCESS;
    const RESULT_FAILED  = LoginLog::RESULT_FAILED;
    // 暴露登录类型常量供 Service 层使用
    const LOGIN_TYPE_PASSWORD       = LoginLog::LOGIN_TYPE_PASSWORD;
    const LOGIN_TYPE_WECHAT         = LoginLog::LOGIN_TYPE_WECHAT;
    const LOGIN_TYPE_SMS            = LoginLog::LOGIN_TYPE_SMS;
    const LOGIN_TYPE_SECURITY_CHANGE = LoginLog::LOGIN_TYPE_SECURITY_CHANGE;
    const LOGIN_TYPE_ADMIN_UNLOCK   = LoginLog::LOGIN_TYPE_ADMIN_UNLOCK;

    public function __construct(?LoginLogRepository $repository = null)
    {
        $repository = $repository ?? new LoginLogRepository();
        parent::__construct($repository);
    }

    /**
     * 记录登录日志
     */
    public function recordLogin($userId, $userType, $loginIp, $loginResult, $message = '', $appId = 0, $userName = '', $loginType = LoginLog::LOGIN_TYPE_PASSWORD, $failReason = '', $userAgent = '', $location = '')
    {
        try {
            $this->logInfo('记录登录日志开始', [
                'user_id' => $userId,
                'user_type' => $userType,
                'login_ip' => $loginIp,
                'login_result' => $loginResult,
                'app_id' => $appId
            ]);

            $log = $this->repository->record($userId, $userType, $loginIp, $loginResult, $message, $appId, $userName, $loginType, $failReason, $userAgent, $location);

            $this->logInfo('记录登录日志成功', ['log_id' => $log->id]);
            return $log;

        } catch (Exception $e) {
            $this->logError('记录登录日志失败', [
                'user_id' => $userId,
                'user_type' => $userType,
                'login_ip' => $loginIp,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户登录日志
     */
    public function getUserLoginLogs($userId, $userType = null, $appId = 0, $limit = 20)
    {
        try {
            return $this->repository->getUserLoginLogs($userId, $userType, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取用户登录日志失败', [
                'user_id' => $userId,
                'user_type' => $userType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取登录统计
     */
    public function getLoginStats($startTime = null, $endTime = null, $appId = 0)
    {
        try {
            return $this->repository->getLoginStats($startTime, $endTime, $appId);

        } catch (Exception $e) {
            $this->logError('获取登录统计失败', [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取IP登录统计
     */
    public function getIpLoginStats($startTime = null, $endTime = null, $appId = 0)
    {
        try {
            return $this->repository->getIpLoginStats($startTime, $endTime, $appId);

        } catch (Exception $e) {
            $this->logError('获取IP登录统计失败', [
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
            $this->logInfo('清理过期登录日志开始', ['days' => $days]);

            $count = $this->repository->cleanExpiredLogs($days);

            $this->logInfo('清理过期登录日志成功', ['count' => $count]);
            return $count;

        } catch (Exception $e) {
            $this->logError('清理过期登录日志失败', [
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取登录日志分页列表
     */
    public function getLoginLogList($page = 1, $limit = 20, array $filters = [])
    {
        try {
            $conditions = [];
            if (isset($filters['user_type']) && $filters['user_type'] !== '') {
                $conditions['user_type'] = $filters['user_type'];
            }
            if (isset($filters['status']) && $filters['status'] !== '') {
                // 部分历史字段名为 login_status
                $conditions['login_status'] = $filters['status'];
            }
            if (!empty($filters['app_id'])) {
                $conditions['app_id'] = $filters['app_id'];
            }

            // 按创建时间或ID倒序
            $orderBy = ['id' => 'desc'];
            return $this->repository->getPaginatedData($page, $limit, $conditions, $orderBy);

        } catch (Exception $e) {
            $this->logError('获取登录日志分页失败', [
                'page' => $page,
                'limit' => $limit,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 根据ID获取登录日志
     */
    public function getLoginLogById($id)
    {
        try {
            return $this->repository->find($id);
        } catch (Exception $e) {
            $this->logError('获取登录日志失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除登录日志
     */
    public function deleteLoginLog($id)
    {
        try {
            return (bool) $this->repository->delete($id);
        } catch (Exception $e) {
            $this->logError('删除登录日志失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 批量删除登录日志
     */
    public function batchDeleteLoginLogs(array $ids)
    {
        try {
            if (empty($ids)) {
                return false;
            }
            return (bool) $this->repository->deleteWhere(['id' => $ids]);
        } catch (Exception $e) {
            $this->logError('批量删除登录日志失败', [
                'ids' => $ids,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 清空指定天数前的登录日志
     */
    public function clearOldLoginLogs($days = 30)
    {
        try {
            $this->repository->cleanExpiredLogs($days);
            return true;
        } catch (Exception $e) {
            $this->logError('清空旧登录日志失败', [
                'days' => $days,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 导出登录日志（占位实现：当前直接返回 true）
     */
    public function exportLoginLogs($startDate = '', $endDate = '')
    {
        try {
            // 这里可以按需实现导出为 CSV/Excel
            return true;
        } catch (Exception $e) {
            $this->logError('导出登录日志失败', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
