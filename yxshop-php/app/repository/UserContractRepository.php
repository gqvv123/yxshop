<?php

namespace app\repository;

use app\model\UserContract;

/**
 * 用户合同仓储
 */
class UserContractRepository extends BaseRepository
{
    protected $model = UserContract::class;

    /**
     * 获取用户合同列表（分页）
     */
    public function getByUserPaginated(int $userId, int $page = 1, int $pageSize = 20)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderBy('id', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 获取用户合同详情
     */
    public function findByUser(int $id, int $userId)
    {
        return $this->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * 获取用户合同统计
     */
    public function getStatsByUser(int $userId): array
    {
        $base = $this->query()->where('user_id', $userId);
        $now = time();

        return [
            'total'   => (clone $base)->count(),
            'signed'  => (clone $base)->where('status', UserContract::STATUS_SIGNED)->count(),
            'pending' => (clone $base)->where('status', UserContract::STATUS_PENDING)->count(),
            'expired' => (clone $base)->where('status', UserContract::STATUS_SIGNED)
                            ->where('end_date', '>', 0)->where('end_date', '<', $now)->count(),
        ];
    }
}
