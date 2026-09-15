<?php

namespace app\repository;

use app\model\AdminApiToken;

/**
 * 管理后台API Token仓储
 */
class AdminApiTokenRepository extends BaseRepository
{
    protected $model = AdminApiToken::class;

    /**
     * 按管理员获取Token
     */
    public function getByAdmin(int $adminId, int $appId = 0)
    {
        $query = $this->query()->where('admin_id', $adminId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 按Token哈希查找
     */
    public function findByTokenHash(string $tokenHash, int $appId = 0)
    {
        $query = $this->query()->where('token_hash', $tokenHash);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->first();
    }

    /**
     * 获取已启用的Token
     */
    public function getEnabled(int $appId = 0)
    {
        $query = $this->query()
            ->where('status', AdminApiToken::STATUS_ENABLED)
            ->where(function ($q) {
                $q->where('expires_at', 0)->orWhere('expires_at', '>', time());
            });
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['admin'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['admin_id'])) {
            $query->where('admin_id', $filters['admin_id']);
        }
        if (!empty($filters['keyword'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['keyword']);
            $query->where('token_name', 'like', '%' . $escaped . '%');
        }
        return $query->with(['admin'])->paginate($pageSize);
    }

    /**
     * 更新最后使用时间
     */
    public function updateLastUsedAt(int $id): AdminApiToken
    {
        $token = $this->findOrFail($id);
        $token->last_used_at = time();
        $token->save();
        return $token;
    }

    /**
     * 禁用Token
     */
    public function disable(int $id): AdminApiToken
    {
        $token = $this->findOrFail($id);
        $token->status = AdminApiToken::STATUS_DISABLED;
        $token->save();
        return $token;
    }

    /**
     * 清理过期Token
     */
    public function cleanExpired(int $appId = 0): int
    {
        $query = $this->query()
            ->where('expires_at', '>', 0)
            ->where('expires_at', '<=', time());
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->update(['status' => AdminApiToken::STATUS_DISABLED]);
    }
}