<?php

namespace app\repository;

use app\model\PurchaseOrder;

/**
 * 采购订单仓储类
 */
class PurchaseOrderRepository extends BaseRepository
{
    protected $model = PurchaseOrder::class;

    /**
     * 获取采购统计
     */
    public function getStats(int $appId, int $supplierId, int $startTs, int $endTs): array
    {
        $result = $this->query()
            ->where('app_id', $appId)
            ->where('supplier_id', $supplierId)
            ->whereBetween('created_at', [$startTs, $endTs])
            ->selectRaw('COUNT(*) as total_orders, SUM(total_amount) as total_sales')
            ->first();

        return [
            'total_orders' => $result->total_orders ?? 0,
            'total_sales' => $result->total_sales ?? 0,
        ];
    }
}
