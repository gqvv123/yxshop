<?php

namespace app\repository;

use app\model\AccountStatement;

/**
 * 平台对账单仓储类
 */
class AccountStatementRepository extends BaseRepository
{
    protected $model = AccountStatement::class;

    /**
     * 分页列表
     */
    public function getPaginatedList(int $appId, int $pageSize = 20, string $status = '')
    {
        $query = $this->query()
            ->where('app_id', $appId)
            ->orderBy('created_at', 'desc');

        if ($status !== '') {
            $query->where('status', (int) $status);
        }

        return $query->paginate($pageSize);
    }

    /**
     * 按ID查找
     */
    public function findById(int $id)
    {
        return $this->query()->where('id', $id)->first();
    }

    /**
     * 确认对账单
     */
    public function confirm(int $id, int $operatorId, ?string $operatorName): int
    {
        return $this->query()->where('id', $id)->update([
            'status' => 1,
            'confirmed_at' => time(),
            'operator_id' => $operatorId,
            'operator_name' => $operatorName,
        ]);
    }
}
