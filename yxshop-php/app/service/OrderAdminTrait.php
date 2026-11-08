<?php

namespace app\service;
use app\model\Order;
use app\model\OrderLog;
use app\model\RefundRecord;
use app\exception\BusinessException;
use Exception;

/**
 * OrderAdminTrait：从 OrderService.php 抽离的getAdminOrderList、getAdminOrderDetails、getOrderItems、updateOrderStatus、updateOrderItemStatus、adminDeleteOrder、getTransactionList、getHotItems、changePrice、freeShipping、ship、resolveExpress、audit、refund、addNote、reviewOrder等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait OrderAdminTrait
{
    public function getAdminOrderList($appId = 0, $params = [], $pageSize = 20)
    {
        return $this->repository->getAdminOrderList((int) $appId, $params, (int) $pageSize);
    }

    public function getAdminOrderDetails($id)
    {
        return $this->repository->findAdminOrderDetail($id);
    }

    public function getOrderItems($orderId)
    {
        return $this->orderItemRepository->getByOrderId((int) $orderId);
    }

    public function updateOrderStatus($id, $status = null, $paymentStatus = null)
    {
        $order = $this->repository->findOrFail($id);
        $data = [];
        if ($status !== null) {
            $data['status'] = $status;
        }
        if ($paymentStatus !== null) {
            $data['pay_status'] = $paymentStatus;
        }
        if (!empty($data)) {
            $order->update($data);
        }
        return $order;
    }

    public function updateOrderItemStatus($id, $status): bool
    {
        $orderItem = $this->orderItemRepository->find($id);
        if (!$orderItem) {
            return false;
        }
        $orderItem->status = $status;
        $orderItem->save();
        return true;
    }

    public function adminDeleteOrder($id): bool
    {
        $order = $this->repository->findOrFail($id);
        $order->deleted_at = time();
        $order->save();
        return true;
    }

    public function getTransactionList($appId = 0, $page = 1, $limit = 20, $status = '', $startTime = null, $endTime = null)
    {
        return $this->repository->getTransactionList(
            (int) $appId, (int) $page, (int) $limit, $status, $startTime, $endTime
        );
    }

    /**
     * 热销商品（按近 30 天销量聚合）
     */
    public function getHotItems(int $limit = 10): array
    {
        return $this->orderItemRepository->getHotItemsStats(30, $limit);
    }

    public function changePrice($orderId, $updatePrice, $reason, $operatorType, $operatorId, $operatorName)
    {
        try {
            $order = $this->repository->findOrFail($orderId);
            if ($order->status != Order::ORDER_STATUS_PENDING) {
                throw new BusinessException('订单状态不允许改价');
            }
            if ($order->pay_status != Order::PAY_STATUS_UNPAID) {
                throw new BusinessException('已付款订单不允许改价');
            }

            $newPayPrice = $order->total_price + $order->express_price - $order->coupon_price - $order->discount_price + $updatePrice;
            if ($newPayPrice < 0) {
                throw new BusinessException('改价后支付金额不能小于0');
            }

            return $this->transaction(function () use ($order, $updatePrice, $newPayPrice, $reason, $operatorType, $operatorId, $operatorName) {
                $oldPayPrice = $order->pay_price;
                $order->update_price = $updatePrice;
                $order->pay_price = $newPayPrice;
                $order->save();

                $order->logChange(
                    OrderLog::CHANGE_TYPE_CHANGE_PRICE,
                    'pay_price',
                    $oldPayPrice,
                    $newPayPrice,
                    $reason,
                    $operatorType,
                    $operatorId,
                    $operatorName
                );

                return $order;
            });
        } catch (Exception $e) {
            $this->logError('改价失败', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function freeShipping($orderId, $reason, $operatorType, $operatorId, $operatorName)
    {
        try {
            $order = $this->repository->findOrFail($orderId);
            if ($order->delivery_status != Order::DELIVERY_STATUS_UNSHIPPED) {
                throw new BusinessException('订单已发货，无法免邮');
            }

            return $this->transaction(function () use ($order, $reason, $operatorType, $operatorId, $operatorName) {
                $oldExpressPrice = $order->express_price;
                $order->express_price = 0;
                $order->pay_price = $order->calculatePayPrice();
                $order->save();

                $order->logChange(
                    OrderLog::CHANGE_TYPE_FREE_SHIPPING,
                    'express_price',
                    $oldExpressPrice,
                    0,
                    $reason,
                    $operatorType,
                    $operatorId,
                    $operatorName
                );

                return $order;
            });
        } catch (Exception $e) {
            $this->logError('免邮失败', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function ship($orderId, $expressId, $expressNo, $operatorType, $operatorId, $operatorName)
    {
        try {
            $order = $this->repository->findOrFail($orderId);
            if ($order->pay_status != Order::PAY_STATUS_PAID) {
                throw new BusinessException('订单未付款，无法发货');
            }
            if ($order->delivery_status != Order::DELIVERY_STATUS_UNSHIPPED) {
                throw new BusinessException('订单已发货');
            }

            return $this->transaction(function () use ($order, $expressId, $expressNo, $operatorType, $operatorId, $operatorName) {
                $order->delivery_status = Order::DELIVERY_STATUS_SHIPPED;
                $order->delivery_time = time();
                $order->express_no = $expressNo;
                $order->save();

                [$company, $expressModelId, $expressCode] = $this->resolveExpress($expressId, $order->app_id);

                $this->orderDeliveryRepository->create([
                    'order_id'     => $order->id,
                    'order_no'     => $order->order_no,
                    'express_id'   => $expressModelId,
                    'express_no'   => $expressNo,
                    'express_code' => $expressCode,
                    'company'      => $company,
                    'type'         => 1,
                    'app_id'       => $order->app_id,
                    'created_at'   => time(),
                    'updated_at'   => time(),
                ]);

                $order->logChange(
                    OrderLog::CHANGE_TYPE_SHIP,
                    'delivery_status',
                    Order::DELIVERY_STATUS_UNSHIPPED,
                    Order::DELIVERY_STATUS_SHIPPED,
                    '',
                    $operatorType,
                    $operatorId,
                    $operatorName
                );

                return $order;
            });
        } catch (Exception $e) {
            $this->logError('发货失败', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 解析快递信息：前端传入的是快递编码(如 SF/YTO)或快递公司ID
     * @return array{0:string,1:int,2:string} [公司名称, express_id, 编码]
     */
    protected function resolveExpress($expressId, $appId): array
    {
        $companyMap = [
            'SF'  => '顺丰速运',
            'YTO' => '圆通速递',
            'ZTO' => '中通快递',
            'STO' => '申通快递',
            'YD'  => '韵达快递',
            'JD'  => '京东物流',
            'EMS' => 'EMS',
        ];

        if (is_numeric($expressId) && (int) $expressId > 0) {
            $express = $this->expressRepository->find((int) $expressId);
            if ($express) {
                return [$express->name, (int) $express->id, (string) $express->code];
            }
            return ['', (int) $expressId, ''];
        }

        $code = (string) $expressId;
        $company = $companyMap[$code] ?? $code;

        return [$company, 0, $code];
    }

    public function audit($orderId, $result, $remark, $operatorType, $operatorId, $operatorName)
    {
        try {
            $order = $this->repository->findOrFail($orderId);
            if ($order->status != Order::ORDER_STATUS_PENDING) {
                throw new BusinessException('订单状态不允许审核');
            }

            return $this->transaction(function () use ($order, $result, $remark, $operatorType, $operatorId, $operatorName) {
                $oldAuditStatus = $order->audit_status;
                if ($result === 'approve' || $result === 10) {
                    $order->audit_status = 10;
                    $order->audit_time = time();
                } else {
                    $order->audit_status = 20;
                    $order->audit_remark = $remark;
                    $order->audit_time = time();
                }
                $order->save();

                $order->logChange(
                    OrderLog::CHANGE_TYPE_AUDIT,
                    'audit_status',
                    $oldAuditStatus,
                    $order->audit_status,
                    $remark,
                    $operatorType,
                    $operatorId,
                    $operatorName
                );

                return $order;
            });
        } catch (Exception $e) {
            $this->logError('审核失败', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function refund($orderId, $refundAmount, $reason, $operatorType, $operatorId, $operatorName)
    {
        try {
            $order = $this->repository->findOrFail($orderId);
            if ($order->pay_status != Order::PAY_STATUS_PAID) {
                throw new BusinessException('订单未付款，无法退款');
            }
            if ($refundAmount > $order->pay_price) {
                throw new BusinessException('退款金额不能大于支付金额');
            }

            return $this->transaction(function () use ($order, $refundAmount, $reason, $operatorType, $operatorId, $operatorName) {
                $this->refundRecordRepository->create([
                    'refund_no' => 'RF' . date('YmdHis') . mt_rand(1000, 9999),
                    'order_id' => $order->id,
                    'order_no' => $order->order_no,
                    'after_sales_id' => 0,
                    'user_id' => $order->user_id,
                    'refund_amount' => $refundAmount,
                    'refund_reason' => $reason,
                    'refund_type' => RefundRecord::TYPE_ORIGINAL,
                    'refund_status' => RefundRecord::STATUS_PENDING,
                    'transaction_id' => $order->transaction_id ?? '',
                    'operator_type' => $operatorType,
                    'operator_id' => $operatorId,
                    'operator_name' => $operatorName,
                    'app_id' => $order->app_id,
                ]);

                $oldPayStatus = $order->pay_status;
                $order->pay_status = Order::REFUND_STATUS_REFUNDING;
                $order->save();

                $order->logChange(
                    OrderLog::CHANGE_TYPE_REFUND,
                    'pay_status',
                    $oldPayStatus,
                    Order::REFUND_STATUS_REFUNDING,
                    $reason,
                    $operatorType,
                    $operatorId,
                    $operatorName
                );

                // 取消分销佣金记录（待结算的）
                $this->cancelOrderAgentCommission($order->id);

                return $order;
            });
        } catch (Exception $e) {
            $this->logError('退款失败', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function addNote($orderId, $note, $append, $operatorType, $operatorId, $operatorName)
    {
        try {
            $order = $this->repository->findOrFail($orderId);

            return $this->transaction(function () use ($order, $note, $append, $operatorType, $operatorId, $operatorName) {
                // 在备注内容前添加操作人标识和时间戳，方便区分是谁填的
                $operatorLabel = $operatorType == 10 ? '用户' : '管理员';
                $displayName = $operatorName ?: ($operatorType == 10 ? '用户' : '管理员');
                $timestamp = date('Y-m-d H:i');
                $noteWithMeta = "[{$operatorLabel} {$displayName} {$timestamp}] {$note}";

                $oldNote = $order->seller_note;
                if ($append && !empty($oldNote)) {
                    $order->seller_note = $oldNote . "\n" . $noteWithMeta;
                } else {
                    $order->seller_note = $noteWithMeta;
                }
                $order->save();

                $order->logChange(
                    OrderLog::CHANGE_TYPE_NOTE,
                    'seller_note',
                    $oldNote,
                    $order->seller_note,
                    $note,
                    $operatorType,
                    $operatorId,
                    $operatorName
                );

                return $order;
            });
        } catch (Exception $e) {
            $this->logError('备注失败', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function reviewOrder($orderId, array $data)
    {
        try {
            $order = $this->repository->findOrFail($orderId);

            if ($order->status != Order::STATUS_COMPLETED) {
                throw new BusinessException('订单未完成，无法评价');
            }
            if ($order->is_comment) {
                throw new BusinessException('订单已评价');
            }

            $userId = $data['user_id'] ?? 0;
            if ($order->user_id != $userId) {
                throw new BusinessException('无权评价此订单');
            }

            return $this->transaction(function () use ($order, $data) {
                $orderItems = $order->items ?? $order->orderGoods ?? [];
                foreach ($orderItems as $orderItem) {
                    $this->commentRepository->create([
                        'score' => (int) ($data['rating'] ?? 5),
                        'content' => $data['content'] ?? '',
                        'images' => $data['images'] ?? [],
                        'is_anonymous' => !empty($data['is_anonymous']) ? 1 : 0,
                        'user_id' => $order->user_id,
                        'order_id' => $order->id,
                        'item_id' => $orderItem->item_id ?? 0,
                        'order_item_id' => $orderItem->id ?? 0,
                        'status' => \app\model\Comment::STATUS_APPROVED,
                        'app_id' => $order->app_id,
                    ]);
                }

                $order->is_comment = 1;
                $order->save();

                return $order;
            });
        } catch (Exception $e) {
            $this->logError('评价失败', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
