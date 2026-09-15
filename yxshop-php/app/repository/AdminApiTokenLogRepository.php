<?php

namespace app\repository;

use app\model\AdminApiTokenLog;

/**
 * 管理后台API Token日志仓储
 */
class AdminApiTokenLogRepository extends BaseRepository
{
    protected $model = AdminApiTokenLog::class;

    /**
     * 按Token获取日志
     */
    public function getByToken(int $tokenId, int $appId = 0)
    {
        $query = $this->query()->where('token_id', $tokenId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('requested_at', 'desc')->get();
    }

    /**
     * 获取最近日志
     */
    public function getRecent(int $limit = 100, int $appId = 0)
    {
        $query = $this->query()->orderBy('requested_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['token'])->limit($limit)->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('requested_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (!empty($filters['token_id'])) {
            $query->where('token_id', $filters['token_id']);
        }
        if (!empty($filters['request_path'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['request_path']);
            $query->where('request_path', 'like', '%' . $escaped . '%');
        }
        if (!empty($filters['request_method'])) {
            $query->where('request_method', $filters['request_method']);
        }
        if (!empty($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }
        return $query->with(['token'])->paginate($pageSize);
    }

    /**
     * 记录API请求
     */
    public function logRequest(int $tokenId, string $path, string $method, string $ip, int $appId = 0): AdminApiTokenLog
    {
        return $this->create([
            'token_id' => $tokenId,
            'request_path' => $path,
            'request_method' => $method,
            'ip_address' => $ip,
            'requested_at' => time(),
            'app_id' => $appId,
        ]);
    }
}