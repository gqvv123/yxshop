<?php

namespace app\repository;

use app\model\OrderItem;

class OrderItemRepository extends BaseRepository
{
    protected $model = OrderItem::class;

    public function getByOrderId($orderId)
    {
        return $this->query()
            ->where('order_id', $orderId)
            ->with(['item'])
            ->get();
    }

    public function updateStatus($id, $status)
    {
        $orderItem = $this->find($id);
        if (!$orderItem) {
            return false;
        }
        $orderItem->status = $status;
        return $orderItem->save();
    }

    public function getHotItemsStats(int $days = 30, int $limit = 10): array
    {
        return $this->query()
            ->selectRaw(
                'item_id, name, SUM(total_num) as total_sales, SUM(total_price) as total_revenue'
            )
            ->where('created_at', '>=', time() - $days * 86400)
            ->groupBy('item_id', 'name')
            ->orderBy('total_sales', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}