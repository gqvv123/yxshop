<?php

namespace app\repository;

use app\model\UserWithdraw;

/**
 * 用户提现仓储
 */
class UserWithdrawRepository extends BaseRepository
{
    protected $model = UserWithdraw::class;

    /**
     * 获取用户提现记录（分页）
     */
    public function getByUserPaginated(int $userId, int $page = 1, int $pageSize = 20)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 获取用户待审核提现数量
     */
    public function countPendingByUser(int $userId): int
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('status', UserWithdraw::STATUS_PENDING)
            ->count();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->with(['user'])->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }
        return $query->paginate($pageSize);
    }
}
