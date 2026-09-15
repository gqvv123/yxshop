<?php

namespace app\repository;

use app\model\LoginLog;

/**
 * 登录日志仓储类
 */
class LoginLogRepository extends BaseRepository
{
    protected $model = LoginLog::class;

    /**
     * 获取用户登录日志
     */
    public function getUserLoginLogs($userId, $userType = null, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc');

        if ($userType !== null) {
            $query->where('user_type', $userType);
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
     * 获取登录统计
     */
    public function getLoginStats($startTime = null, $endTime = null, $appId = 0)
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

        return [
            'total_count' => $query->count(),
            'success_count' => $query->where('login_status', LoginLog::STATUS_SUCCESS)->count(),
            'failed_count' => $query->where('login_status', LoginLog::STATUS_FAILED)->count(),
            'user_count' => $query->where('user_type', LoginLog::USER_TYPE_USER)->count(),
            'admin_count' => $query->where('user_type', LoginLog::USER_TYPE_ADMIN)->count(),
        ];
    }

    /**
     * 获取IP登录统计
     */
    public function getIpLoginStats($startTime = null, $endTime = null, $appId = 0)
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

        return $query->selectRaw('login_ip, COUNT(*) as count, MAX(created_at) as last_login')
            ->groupBy('login_ip')
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
     * 记录登录日志
     */
    public function record($userId, $userType, $loginIp, $loginResult, $loginMessage = '', $appId = 0, $userName = '', $loginType = LoginLog::LOGIN_TYPE_PASSWORD, $failReason = '', $userAgent = '', $location = '')
    {
        $log = new LoginLog();
        $log->user_type = $userType;
        $log->user_id = $userId;
        $log->user_name = $userName;
        $log->login_type = $loginType;
        $log->login_result = $loginResult;
        $log->fail_reason = $failReason;
        $log->login_ip = $loginIp;
        $log->user_agent = $userAgent;
        $log->location = $location;
        $log->app_id = $appId;
        $log->created_at = time();
        $log->login_message = $loginMessage;
        $log->login_status = $loginResult === LoginLog::RESULT_SUCCESS ? LoginLog::STATUS_SUCCESS : LoginLog::STATUS_FAILED;
        $log->updated_at = time();
        $log->save();

        return $log;
    }
}
