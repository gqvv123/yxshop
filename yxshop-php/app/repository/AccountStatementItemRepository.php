<?php

namespace app\repository;

use app\model\AccountStatementItem;

/**
 * 平台对账单明细仓储类
 */
class AccountStatementItemRepository extends BaseRepository
{
    protected $model = AccountStatementItem::class;

    /**
     * 按对账单ID获取明细
     */
    public function getByStatementId(int $statementId)
    {
        return $this->query()->where('statement_id', $statementId)->get();
    }

    /**
     * 批量创建明细
     */
    public function batchCreate(array $items): bool
    {
        return $this->model->insert($items);
    }
}
