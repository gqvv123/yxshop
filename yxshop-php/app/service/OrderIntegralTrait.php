<?php

namespace app\service;
use app\model\Order;
use app\exception\BusinessException;

/**
 * OrderIntegralTrait：从 OrderService.php 抽离的getCustomerProfile、getOrderStats、getOrderByYear、getIntegralExchangeRecords、createIntegralExchangeOrder、getSeckillOrders等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait OrderIntegralTrait
{
    public function getCustomerProfile(int $orderId): array
    {
        $order = $this->repository->findOrFail($orderId);
        $user = $this->userRepository->find($order->user_id);

        $stats = $this->repository->getUserOrderStatsForProfile($order->user_id, $order->app_id);

        $address = '-';
        if ($order->address) {
            $address = $order->address->full_address ?? '-';
        }

        return [
            'name' => $user ? ($user->nickname ?? $user->username ?? '-') : '-',
            'level' => $user ? ($user->level_text ?? '-') : '-',
            'totalSpent' => $stats['totalSpent'],
            'returnCount' => $stats['returnCount'],
            'preference' => '-',
            'address' => $address,
        ];
    }

    public function getOrderStats($appId): array
    {
        return [
            'total_order' => $this->repository->countByApp((int) $appId),
        ];
    }

    public function getOrderByYear($appId): array
    {
        return $this->repository->getOrderCountByYear((int) $appId);
    }

    /**
     * 积分兑换订单列表
     */
    public function getIntegralExchangeRecords(int $userId, int $page = 1, int $pageSize = 20)
    {
        return $this->repository->getIntegralOrdersByUser($userId, $page, $pageSize);
    }

    /**
     * 创建积分兑换订单
     */
    public function createIntegralExchangeOrder(int $userId, array $params): array
    {
        $itemId = $params['item_id'] ?? '';
        $quantity = max(1, (int) ($params['quantity'] ?? 1));
        $addressId = $params['address_id'] ?? '';

        if (empty($itemId)) {
            throw new BusinessException('商品ID不能为空');
        }

        $itemService = new ItemService();
        $item = $itemService->findPointExchangeItem($itemId);
        if (!$item) {
            throw new BusinessException('积分商品不存在或已下架');
        }

        $user = $this->userService->find($userId);
        $pointsNeeded = (int) ($item->points_exchange * $quantity);

        if ((float) $user->integral < $pointsNeeded) {
            throw new BusinessException('积分不足，还需' . ($pointsNeeded - (int) $user->integral) . '积分');
        }

        if ($item->stock < $quantity && $item->is_physical) {
            throw new BusinessException('库存不足');
        }

        $orderNo = 'PT' . date('YmdHis') . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);
        $integralService = new IntegralService();

        $orderData = [
            'order_no'        => $orderNo,
            'user_id'         => $userId,
            'total_price'     => 0,
            'pay_price'       => 0,
            'pay_status'      => Order::PAY_STATUS_PAID,
            'status'          => Order::ORDER_STATUS_PENDING,
            'delivery_status' => Order::DELIVERY_STATUS_UNSHIPPED,
            'receipt_status'  => Order::RECEIPT_STATUS_UNRECEIVED,
            'is_integral'     => 1,
            'item_id'         => $itemId,
            'app_id'          => $item->app_id,
            'source'          => 10,
        ];

        $itemRecordData = [
            'item_id'       => $itemId,
            'item_name'     => $item->name,
            'spec_key'      => '',
            'spec_key_name' => '',
            'quantity'      => $quantity,
            'sale_price'    => 0,
            'total_price'   => 0,
            'app_id'        => $item->app_id,
        ];

        $order = $this->repository->createIntegralOrder($orderData, $itemRecordData);

        $integralService->reduceIntegral(
            $userId,
            $pointsNeeded,
            '积分兑换商品：' . $item->name . ' x' . $quantity,
            IntegralService::TYPE_SPEND,
            $order->id
        );

        if ($item->is_physical) {
            $itemService->deductStock($itemId, $quantity);
        }

        $remainingPoints = $this->userService->find($userId)->integral ?? 0;

        return [
            'order_id'         => $order->id,
            'order_no'         => $orderNo,
            'points_used'      => $pointsNeeded,
            'remaining_points' => (float) $remainingPoints,
        ];
    }

    /**
     * 秒杀订单列表
     */
    public function getSeckillOrders(int $userId, int $page = 1, int $pageSize = 20)
    {
        return $this->repository->getSeckillOrdersByUser($userId, $page, $pageSize);
    }
}
