<?php

namespace app\repository;

use app\model\UserOauth;

/**
 * 用户第三方授权仓储
 */
class UserOauthRepository extends BaseRepository
{
    protected $model = UserOauth::class;

    /**
     * 按用户获取授权记录
     */
    public function getByUser(int $userId, int $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 按类型和OpenID查找
     */
    public function findByTypeAndOpenid(string $oauthType, string $openid, int $appId = 0)
    {
        $query = $this->query()
            ->where('oauth_type', $oauthType)
            ->where('openid', $openid);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->first();
    }

    /**
     * 按UnionID查找
     */
    public function findByUnionid(string $unionid, int $appId = 0)
    {
        $query = $this->query()->where('unionid', $unionid);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->first();
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
        if (!empty($filters['oauth_type'])) {
            $query->where('oauth_type', $filters['oauth_type']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['keyword'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['keyword']);
            $query->where('nickname', 'like', '%' . $escaped . '%');
        }
        return $query->with(['user'])->paginate($pageSize);
    }

    /**
     * 更新最后使用时间
     */
    public function updateLastUsedAt(int $id): UserOauth
    {
        $oauth = $this->findOrFail($id);
        $oauth->last_used_at = time();
        $oauth->save();
        return $oauth;
    }
}