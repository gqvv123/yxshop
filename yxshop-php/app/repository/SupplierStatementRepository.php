<?php

namespace app\repository;

use app\model\SupplierStatement;

/**
 * 供应商对账单仓储类
 */
class SupplierStatementRepository extends BaseRepository
{
    protected $model = SupplierStatement::class;

    /**
     * 分页列表
     */
    public function getPaginatedList(int $appId, int $pageSize = 20, int $supplierId = 0)
    {
        $query = $this->query()
            ->where('app_id', $appId)
            ->orderBy('created_at', 'desc');

        if ($supplierId > 0) {
            $query->where('supplier_id', $supplierId);
        }

        return $query->paginate($pageSize);
    }
}
