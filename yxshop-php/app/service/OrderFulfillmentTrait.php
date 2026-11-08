<?php

namespace app\service;
use app\model\Order;
use app\model\OrderLog;
use app\model\AfterSales;
use app\model\UserCoupon;
use app\exception\BusinessException;
use Exception;

/**
 * OrderFulfillmentTrait：从 OrderService.php 抽离的getLogistics、remindShip、deleteOrder、cancelOrder、confirmOrder、refundOrder、restoreStock、restoreCoupon、addSales、giveIntegral、createOrderAgentCommission、cancelOrderAgentCommission、getOrderTracking等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait OrderFulfillmentTrait
{
    /**
     * 获取订单物流信息
     *
     * @param int $orderId
     * @param int $userId
     * @return array
     */
    public function getLogistics(int $orderId, int $userId): array
    {
        try {
            $order = $this->repository->findOrderForUser($orderId, $userId);
            if (!$order) {
                throw new BusinessException('订单不存在或无权访问', 404);
            }

            $deliveries = $this->orderDeliveryRepository->getByOrderIdWithExpress($orderId)
                ->map(function ($delivery) {
                    return [
                        'id'             => $delivery->id,
                        'express_name'   => $delivery->express_name ?? ($delivery->express->name ?? ''),
                        'express_no'     => $delivery->express_no ?? '',
                        'express_code'   => $delivery->express_code ?? '',
                        'status'         => $delivery->status,
                        'status_text'    => $delivery->status_text ?? '',
                        'delivery_time'  => $delivery->delivery_time,
                        'receive_time'   => $delivery->receive_time,
                        'remark'         => $delivery->remark ?? '',
                    ];
                });

            // 物流轨迹（简化：实际应调快递100/快递鸟等第三方 API）
            $tracks = [];
            if ($deliveries->isNotEmpty()) {
                $first = $deliveries->first();
                $tracks = [
                    [
                        'time'    => $first['delivery_time'] ?? time(),
                        'content' => '卖家已发货，' . $first['express_name'] . '运单号：' . $first['express_no'],
                    ],
                ];
                if ($order->receipt_status == Order::RECEIPT_STATUS_RECEIVED) {
                    $tracks[] = [
                        'time'    => $order->receipt_time ?? time(),
                        'content' => '已签收',
                    ];
                }
            }

            return [
                'order_id'    => $orderId,
                'order_no'    => $order->order_no,
                'deliveries'  => $deliveries,
                'tracks'      => $tracks,
            ];
        } catch (Exception $e) {
            $this->logError('获取订单物流失败', ['order_id' => $orderId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 提醒发货（限制每订单每24小时一次）
     *
     * @param int $orderId
     * @param int $userId
     * @return array
     */
    public function remindShip(int $orderId, int $userId): array
    {
        try {
            $order = $this->repository->findOrderForUser($orderId, $userId);
            if (!$order) {
                throw new BusinessException('订单不存在或无权访问', 404);
            }

            if ($order->delivery_status != Order::DELIVERY_STATUS_UNSHIPPED) {
                throw new BusinessException('订单已发货，无需提醒');
            }

            // 检查是否已提醒过（简化：用 OrderLog 查最近 remind_ship 记录）
            $lastRemind = $this->orderLogRepository->getLastRemindByOrder($orderId);

            if ($lastRemind && (time() - $lastRemind->created_at) < 86400) {
                $hoursLeft = ceil((86400 - (time() - $lastRemind->created_at)) / 3600);
                throw new BusinessException("已提醒过发货，请{$hoursLeft}小时后再试");
            }

            $this->orderLogRepository->create([
                'order_id'      => $orderId,
                'order_no'      => $order->order_no,
                'change_type'   => 90,
                'change_field'  => 'remind_ship',
                'old_value'      => 0,
                'new_value'      => 1,
                'change_reason'  => '用户催促发货',
                'operator_type'  => 20,
                'operator_id'    => $userId,
                'operator_name'  => '用户',
                'app_id'         => $order->app_id,
                'created_at'     => time(),
            ]);

            $this->logInfo('提醒发货成功', ['order_id' => $orderId, 'user_id' => $userId]);
            return ['success' => true, 'message' => '已提醒卖家尽快发货'];
        } catch (Exception $e) {
            $this->logError('提醒发货失败', ['order_id' => $orderId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除订单（已完成/已取消订单，软删除）
     *
     * @param int $orderId
     * @param int $userId
     * @return bool
     */
    public function deleteOrder(int $orderId, int $userId): bool
    {
        try {
            $order = $this->repository->findOrderForUser($orderId, $userId);
            if (!$order) {
                throw new BusinessException('订单不存在或无权访问', 404);
            }

            // 只有已完成或已取消的订单可以删除
            if (!in_array($order->status, [Order::ORDER_STATUS_COMPLETE, Order::ORDER_STATUS_CANCEL])) {
                throw new BusinessException('当前订单状态不允许删除');
            }

            $order->deleted_at = time();
            $order->save();

            $order->logChange(100, 'status', $order->status, 0, '用户删除订单', 20, $userId, '用户');

            $this->logInfo('删除订单成功', ['order_id' => $orderId, 'user_id' => $userId]);
            return true;
        } catch (Exception $e) {
            $this->logError('删除订单失败', ['order_id' => $orderId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 取消订单（事务：改状态 + 恢复库存 + 恢复优惠券）
     */
    public function cancelOrder($orderId, $userId, $reason = '')
    {
        try {
            $this->logInfo('取消订单开始', ['order_id' => $orderId, 'user_id' => $userId]);

            $order = $this->repository->findOrderForUser((int) $orderId, (int) $userId);
            if (!$order) {
                throw new BusinessException('订单不存在或无权访问', 404);
            }
            if (!$order->canCancel()) {
                throw new BusinessException('订单不能取消');
            }

            return $this->transaction(function () use ($order, $reason, $userId) {
                $order->updateStatus(Order::ORDER_STATUS_CANCEL, $reason, 10, $userId, '用户');
                $this->restoreStock($order->items);
                if ($order->coupon_id > 0) {
                    $this->restoreCoupon($order->user_id, $order->coupon_id);
                }
                // 取消分销佣金记录（待结算的）
                $this->cancelOrderAgentCommission($order->id);
                return $order;
            });
        } catch (Exception $e) {
            $this->logError('取消订单失败', ['order_id' => $orderId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 确认收货（事务：改收货状态 + 加销量 + 发积分）
     */
    public function confirmOrder($orderId, $userId)
    {
        try {
            $this->logInfo('确认收货开始', ['order_id' => $orderId, 'user_id' => $userId]);

            $order = $this->repository->findOrderForUser((int) $orderId, (int) $userId);
            if (!$order) {
                throw new BusinessException('订单不存在或无权访问', 404);
            }
            if (!$order->canReceive()) {
                throw new BusinessException('订单不能确认收货');
            }

            return $this->transaction(function () use ($order, $userId) {
                $order->updateReceiptStatus(Order::RECEIPT_STATUS_RECEIVED, '用户确认收货', 10, $userId, '用户');
                $this->addSales($order->items);
                $this->giveIntegral($order);
                // 创建分销佣金记录（推荐人分润）
                $this->createOrderAgentCommission($order);
                return $order;
            });
        } catch (Exception $e) {
            $this->logError('确认收货失败', ['order_id' => $orderId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 申请退款（事务）
     */
    public function refundOrder($orderId, $userId, $reason = '')
    {
        try {
            $this->logInfo('申请退款开始', ['order_id' => $orderId, 'user_id' => $userId]);

            $order = $this->repository->findOrderForUser((int) $orderId, (int) $userId);
            if (!$order) {
                throw new BusinessException('订单不存在或无权访问', 404);
            }
            if (!$order->canApplyService()) {
                throw new BusinessException('订单不能申请退款');
            }

            return $this->transaction(function () use ($order, $reason, $userId) {
                $afterSalesService = new AfterSalesService();
                $afterSalesService->apply([
                    'order_id' => $order->id,
                    'user_id'  => $userId,
                    'sub_type' => AfterSales::TYPE_REFUND,
                    'reasons'  => $reason,
                    'app_id'   => $order->app_id,
                ]);
                $order->logChange(50, 'refund_status', Order::REFUND_STATUS_NONE, Order::REFUND_STATUS_REFUNDING, $reason, 10, $userId, '用户');
                return $order;
            });
        } catch (Exception $e) {
            $this->logError('申请退款失败', ['order_id' => $orderId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function restoreStock($orderGoods): void
    {
        foreach ($orderGoods as $orderGood) {
            $goods = $this->itemService->findOrFail($orderGood->item_id);
            $goods->addStock($orderGood->total_num, $orderGood->item_sku_id ?? 0, '订单取消恢复库存');
        }
    }

    private function restoreCoupon($userId, $couponId): void
    {
        $userCoupon = $this->userCouponRepository->findByUserAndCoupon(
            (int) $userId, (int) $couponId, UserCoupon::STATUS_USED
        );
        if ($userCoupon) {
            $userCoupon->status = UserCoupon::STATUS_UNUSED;
            $userCoupon->order_id = 0;
            $userCoupon->use_time = 0;
            $userCoupon->save();
        }
    }

    private function addSales($orderGoods): void
    {
        foreach ($orderGoods as $orderGood) {
            $goods = $this->itemService->findOrFail($orderGood->item_id);
            $goods->addSales($orderGood->total_num);
        }
    }

    private function giveIntegral($order): void
    {
        $totalIntegral = 0;
        foreach ($order->items as $orderGood) {
            $totalIntegral += $orderGood->calculateTotalIntegral();
        }
        if ($totalIntegral > 0) {
            $user = $this->userService->findOrFail($order->user_id);
            $user->addIntegral($totalIntegral, '订单完成奖励', 1, $order->id);
        }
    }

    /**
     * 创建分销佣金记录
     *
     * 委托 OrderAgentService::createForOrder 完成：
     *   - 查下单用户的 referrer_id（推荐人）
     *   - 若推荐人是已审核分销商，按佣金比例创建 OrderAgent 记录
     *   - 支持二级分销（推荐人的推荐人按半比例）
     *   - 已存在记录则跳过（防重复）
     */
    private function createOrderAgentCommission($order): void
    {
        try {
            $orderAgentService = new OrderAgentService();
            $orderAgentService->createForOrder($order);
        } catch (\Throwable $e) {
            // 分销记录创建失败不影响确认收货主流程
            $this->logWarning('分销佣金记录创建失败', [
                'order_id' => $order->id,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    /**
     * 取消分销佣金记录（订单退款/取消时调用）
     *
     * 委托 OrderAgentService::cancelByOrderId 完成：
     *   - 只取消 confirm=0（待结算）的记录
     *   - 已结算的不动（需走退款扣回流程）
     *   - 失败不影响主流程
     */
    private function cancelOrderAgentCommission($orderId): void
    {
        try {
            $orderAgentService = new OrderAgentService();
            $orderAgentService->cancelByOrderId((int) $orderId);
        } catch (\Throwable $e) {
            $this->logWarning('分销佣金记录取消失败', [
                'order_id' => $orderId,
                'error'    => $e->getMessage(),
            ]);
        }
    }

    public function getOrderTracking(int $orderId): array
    {
        $order = $this->repository->findOrFail($orderId);
        $deliveries = $this->repository->getOrderDeliveries($orderId);

        $nodes = [];
        foreach ($deliveries as $d) {
            if ($d->delivery_time) {
                $nodes[] = ['time' => $d->delivery_time, 'description' => '已发货 - ' . ($d->express_name ?: '快递') . ' ' . ($d->express_no ?: ''), 'status' => 'shipped'];
            }
            if ($d->receive_time) {
                $nodes[] = ['time' => $d->receive_time, 'description' => '已签收', 'status' => 'received'];
            }
        }

        if ($order->pay_time) {
            $nodes[] = ['time' => $order->pay_time, 'description' => '买家已付款', 'status' => 'paid'];
        }
        $nodes[] = ['time' => $order->created_at, 'description' => '订单创建', 'status' => 'created'];

        usort($nodes, function ($a, $b) { return (int)$b['time'] - (int)$a['time']; });

        $expressName = $deliveries->first() ? $deliveries->first()->express_name : '';
        $expressNo = $deliveries->first() ? $deliveries->first()->express_no : '';

        return [
            'orderId' => $orderId,
            'expressName' => $expressName,
            'expressNo' => $expressNo,
            'nodes' => $nodes,
        ];
    }
}
