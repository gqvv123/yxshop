<?php

namespace app\service;

use app\repository\OrderRepository;
use app\repository\OrderItemRepository;
use app\repository\OrderAddressRepository;
use app\repository\UserCouponRepository;
use app\repository\UserAddressRepository;
use app\repository\OrderDeliveryRepository;
use app\repository\OrderLogRepository;
use app\repository\CommentRepository;
use app\repository\ItemRepository;
use app\repository\UserRepository;
use app\repository\ExpressRepository;
use app\repository\RefundRecordRepository;
use app\model\Order;
use app\model\OrderLog;
use app\model\AfterSales;
use app\model\UserCoupon;
use app\model\RefundRecord;
use app\exception\BusinessException;
use app\validate\OrderValidate;
use support\Db;
use Exception;

/**
 * 订单服务类
 *
 * 4 层架构示范：所有数据访问通过 Repository 意图揭示方法（不在 Service 里写 ->query()->where(...)）。
 *
 * @property OrderRepository $repository
 */
class OrderService extends BaseService
{
    protected $itemService;
    protected $userService;
    protected $orderItemRepository;
    protected $orderAddressRepository;
    protected $userCouponRepository;
    protected $userAddressRepository;
    protected $orderDeliveryRepository;
    protected $orderLogRepository;
    protected $commentRepository;
    protected $itemRepository;
    protected $userRepository;
    protected $expressRepository;
    protected $refundRecordRepository;

    public function __construct(?OrderRepository $repository = null)
    {
        $repository = $repository ?? new OrderRepository();
        parent::__construct($repository);
        $this->itemService = new ItemService();
        $this->userService = new UserService();
        $this->orderItemRepository = new OrderItemRepository();
        $this->orderAddressRepository = new OrderAddressRepository();
        $this->userCouponRepository = new UserCouponRepository();
        $this->userAddressRepository = new UserAddressRepository();
        $this->orderDeliveryRepository = new OrderDeliveryRepository();
        $this->orderLogRepository = new OrderLogRepository();
        $this->commentRepository = new CommentRepository();
        $this->itemRepository = new ItemRepository();
        $this->userRepository = new UserRepository();
        $this->expressRepository = new ExpressRepository();
        $this->refundRecordRepository = new RefundRecordRepository();
    }

    // ============================================================
    // 用户端 用例
    // ============================================================

    /**
     * 创建订单
     *
     * 修复了原代码两处 bug：
     *   1) 整段没有事务，5 表写入裸奔（已加 transaction）
     *   2) 用 $order->order_id 取主键，但表没有 order_id 字段（已改为 $order->id）
     */
    public function createOrder(array $data)
    {
        try {
            $this->logInfo('创建订单开始', ['data' => $data]);
            $this->validateWith(OrderValidate::class, 'api_create', $data);

            // 业务前置校验（无写库副作用，事务外执行）
            $this->userService->findOrFail($data['user_id']);
            $itemModels = $this->preloadItems($data['items']);
            $this->checkStock($data['items'], $itemModels);
            $orderAmount = $this->calculateOrderAmount($data['items'], $itemModels);
            $couponDiscount = !empty($data['coupon_id'])
                ? $this->processCoupon($data['user_id'], $data['coupon_id'], $orderAmount)
                : 0;
            $shippingFee = $this->calculateShippingFee($data['items'], $data['address_id']);
            $payAmount = $orderAmount + $shippingFee - $couponDiscount;

            return $this->transaction(function () use ($data, $orderAmount, $payAmount, $shippingFee, $couponDiscount, $itemModels) {
                $order = $this->repository->create([
                    'order_no'        => Order::generateOrderNo(),
                    'user_id'         => $data['user_id'],
                    'app_id'          => $data['app_id'],
                    'total_price'     => $orderAmount,
                    'pay_price'       => $payAmount,
                    'express_price'   => $shippingFee,
                    'coupon_id'       => $data['coupon_id'] ?? 0,
                    'coupon_price'    => $couponDiscount,
                    'status'          => Order::ORDER_STATUS_PENDING,
                    'pay_status'      => Order::PAY_STATUS_UNPAID,
                    'delivery_status' => Order::DELIVERY_STATUS_UNSHIPPED,
                    'receipt_status'  => Order::RECEIPT_STATUS_UNRECEIVED,
                    'buyer_note'      => $data['remark'] ?? $data['buyer_note'] ?? '',
                    'created_at'      => time(),
                    'updated_at'      => time(),
                ]);

                $this->createOrderItems($order->id, $data['items'], $itemModels);
                $this->createOrderAddress($order->id, $data['address_id'], $data['app_id']);
                $this->reduceStock($data['items'], $itemModels);
                $order->logChange(10, 'status', 0, Order::ORDER_STATUS_PENDING, '订单创建');

                $this->logInfo('创建订单成功', ['order_id' => $order->id]);
                return $order;
            });
        } catch (Exception $e) {
            $this->logError('创建订单失败', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取用户订单列表（含分页元数据），支持 tab 过滤
     *
     * @param int $userId
     * @param int $page
     * @param int $pageSize
     * @param int|null $status  原始 status 值（兼容旧接口）
     * @param string|null $tab  tab 过滤：pending_payment|pending_shipment|pending_receipt|pending_review|refunding
     * @return array
     */
    public function getOrderList($userId, $page = 1, $pageSize = 20, $status = null, $tab = null)
    {
        try {
            $userId = (int) $userId;
            $page = max(1, (int) $page);
            $pageSize = min(50, max(1, (int) $pageSize));

            $base = $this->repository->query()
                ->where('user_id', $userId)
                ->with(['items.item.images', 'address']);

            // tab 过滤优先于 status
            if ($tab) {
                switch ($tab) {
                    case 'pending_payment':
                        $base->where('status', Order::ORDER_STATUS_PENDING)
                             ->where('pay_status', Order::PAY_STATUS_UNPAID);
                        break;
                    case 'pending_shipment':
                        $base->where('status', Order::ORDER_STATUS_PENDING)
                             ->where('pay_status', Order::PAY_STATUS_PAID)
                             ->where('delivery_status', Order::DELIVERY_STATUS_UNSHIPPED);
                        break;
                    case 'pending_receipt':
                        $base->where('status', Order::ORDER_STATUS_PENDING)
                             ->where('pay_status', Order::PAY_STATUS_PAID)
                             ->where('delivery_status', Order::DELIVERY_STATUS_SHIPPED)
                             ->where('receipt_status', Order::RECEIPT_STATUS_UNRECEIVED);
                        break;
                    case 'pending_review':
                        $base->where('status', Order::ORDER_STATUS_COMPLETE)
                             ->where('pay_status', Order::PAY_STATUS_PAID)
                             ->where('is_comment', 0);
                        break;
                    case 'refunding':
                        $base->where('pay_status', Order::REFUND_STATUS_REFUNDING);
                        break;
                    // 'all' or unknown → no filter
                }
            } elseif ($status !== null) {
                $base->where('status', (int) $status);
            }

            $total = (clone $base)->count();
            $offset = ($page - 1) * $pageSize;
            $items = $base->orderBy('id', 'desc')->offset($offset)->limit($pageSize)->get();

            // 附加可操作按钮列表 + 补全商品图片
            $items->each(function ($order) {
                $order->actions = $this->getOrderActions($order);
                $order->items->each(function ($item) {
                    $img = $item->getRawOriginal('image');
                    if ($img === '0' || $img === '' || $img === null) {
                        $filled = false;
                        $rel = $item->item ?? null;
                        if ($rel && $rel->relationLoaded('images')) {
                            $first = $rel->getRelation('images')->first();
                            if ($first && !empty($first->image_id) && $first->image_id !== '0') {
                                $item->image = $first->image_id;
                                $filled = true;
                            }
                        }
                        if (!$filled) {
                            $item->image = '';
                        }
                    }
                });
            });

            return [
                'data' => $items,
                'list' => $items,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ];
        } catch (Exception $e) {
            $this->logError('获取订单列表失败', [
                'user_id' => $userId, 'page' => $page, 'page_size' => $pageSize, 'status' => $status, 'tab' => $tab,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户各状态订单数量（用于个人中心/订单列表 tab 角标）
     *
     * @param int $userId
     * @return array
     */
    public function getOrderStatusCounts(int $userId): array
    {
        try {
            $base = $this->repository->query()->where('user_id', $userId);

            return [
                'all'              => (clone $base)->count(),
                'pending_payment'  => (clone $base)
                    ->where('status', Order::ORDER_STATUS_PENDING)
                    ->where('pay_status', Order::PAY_STATUS_UNPAID)
                    ->count(),
                'pending_shipment' => (clone $base)
                    ->where('status', Order::ORDER_STATUS_PENDING)
                    ->where('pay_status', Order::PAY_STATUS_PAID)
                    ->where('delivery_status', Order::DELIVERY_STATUS_UNSHIPPED)
                    ->count(),
                'pending_receipt'  => (clone $base)
                    ->where('status', Order::ORDER_STATUS_PENDING)
                    ->where('pay_status', Order::PAY_STATUS_PAID)
                    ->where('delivery_status', Order::DELIVERY_STATUS_SHIPPED)
                    ->where('receipt_status', Order::RECEIPT_STATUS_UNRECEIVED)
                    ->count(),
                'pending_review'   => (clone $base)
                    ->where('status', Order::ORDER_STATUS_COMPLETE)
                    ->where('pay_status', Order::PAY_STATUS_PAID)
                    ->where('is_comment', 0)
                    ->count(),
                'refunding'        => (clone $base)
                    ->where('pay_status', Order::REFUND_STATUS_REFUNDING)
                    ->count(),
            ];
        } catch (Exception $e) {
            $this->logError('获取订单状态计数失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return [
                'all' => 0, 'pending_payment' => 0, 'pending_shipment' => 0,
                'pending_receipt' => 0, 'pending_review' => 0, 'refunding' => 0,
            ];
        }
    }

    /**
     * 根据订单状态返回可操作按钮列表
     */
    private function getOrderActions(Order $order): array
    {
        $actions = [];

        if ($order->canPay()) {
            $actions[] = ['action' => 'pay', 'label' => '去支付'];
        }
        if ($order->canCancel()) {
            $actions[] = ['action' => 'cancel', 'label' => '取消订单'];
        }
        if ($order->canReceive()) {
            $actions[] = ['action' => 'confirm', 'label' => '确认收货'];
        }
        if ($order->canComment()) {
            $actions[] = ['action' => 'review', 'label' => '评价'];
        }
        if ($order->canApplyService()) {
            $actions[] = ['action' => 'refund', 'label' => '申请售后'];
        }

        // 已完成订单可再次购买
        if ($order->status == Order::ORDER_STATUS_COMPLETE) {
            $actions[] = ['action' => 'reorder', 'label' => '再次购买'];
            $actions[] = ['action' => 'delete', 'label' => '删除订单'];
        }
        // 已取消订单可删除
        if ($order->status == Order::ORDER_STATUS_CANCEL) {
            $actions[] = ['action' => 'delete', 'label' => '删除订单'];
        }
        // 待发货可催促
        if ($order->status == Order::ORDER_STATUS_PENDING
            && $order->pay_status == Order::PAY_STATUS_PAID
            && $order->delivery_status == Order::DELIVERY_STATUS_UNSHIPPED) {
            $actions[] = ['action' => 'remind_ship', 'label' => '提醒发货'];
        }

        return $actions;
    }

    /**
     * 再次购买 — 将历史订单中的商品加入购物车
     *
     * @param int $orderId
     * @param int $userId
     * @return array 加入购物车的商品列表
     */
    public function reorder(int $orderId, int $userId): array
    {
        try {
            $this->logInfo('再次购买开始', ['order_id' => $orderId, 'user_id' => $userId]);

            $order = $this->repository->findOrderForUser($orderId, $userId);
            if (!$order) {
                throw new BusinessException('订单不存在或无权访问', 404);
            }

            $cartService = new CartService();
            $addedItems = [];

            foreach ($order->items as $orderItem) {
                $item = $this->itemRepository->find($orderItem->item_id);
                if (!$item || !$item->is_on_sale) {
                    continue;
                }

                $cartService->addToCart([
                    'user_id'       => $userId,
                    'item_id'       => $orderItem->item_id,
                    'spec_key'      => $orderItem->spec_key ?? '',
                    'spec_key_name' => $orderItem->spec_key_name ?? '',
                    'quantity'      => $orderItem->quantity,
                    'app_id'        => $order->app_id,
                ]);

                $addedItems[] = [
                    'item_id'   => $orderItem->item_id,
                    'item_name' => $orderItem->item_name,
                    'quantity'  => $orderItem->quantity,
                ];
            }

            $this->logInfo('再次购买成功', ['order_id' => $orderId, 'added' => count($addedItems)]);
            return ['added_items' => $addedItems, 'count' => count($addedItems)];
        } catch (Exception $e) {
            $this->logError('再次购买失败', ['order_id' => $orderId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

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
     * 获取订单详情（带关联），可选用户校验
     */
    public function getOrderDetail($orderId, $userId = null)
    {
        try {
            $order = $this->repository->findOrderWithRelations((int) $orderId, $userId !== null ? (int) $userId : null);
            if (!$order) {
                throw new BusinessException('订单不存在或无权访问', 404);
            }
            // 补全商品图片（order_items.image 为空时从商品主图取）
            $order->items->each(function ($item) {
                $img = $item->getRawOriginal('image');
                if ($img === '0' || $img === '' || $img === null) {
                    $filled = false;
                    $rel = $item->item ?? null;
                    if ($rel && $rel->relationLoaded('images')) {
                        $first = $rel->getRelation('images')->first();
                        if ($first && !empty($first->image_id) && $first->image_id !== '0') {
                            $item->image = $first->image_id;
                            $filled = true;
                        }
                    }
                    if (!$filled) {
                        $item->image = '';
                    }
                }
            });
            return $order;
        } catch (Exception $e) {
            $this->logError('获取订单详情失败', ['order_id' => $orderId, 'user_id' => $userId, 'error' => $e->getMessage()]);
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

    // ============================================================
    // 创建订单 内部步骤
    // ============================================================

    private function preloadItems(array $items)
    {
        $itemIds = array_column($items, 'item_id');
        $itemIds = array_map('intval', array_unique($itemIds));
        return $this->itemService->repository->query()->whereIn('id', $itemIds)->get()->keyBy('id');
    }

    private function checkStock(array $items, $itemModels)
    {
        foreach ($items as $item) {
            $goods = $itemModels[$item['item_id']] ?? null;
            if (!$goods) {
                throw new BusinessException("商品不存在: {$item['item_id']}");
            }
            if (!$goods->hasStock($item['quantity'])) {
                throw new BusinessException("商品 {$goods->name} 库存不足");
            }
        }
    }

    private function calculateOrderAmount(array $items, $itemModels)
    {
        $total = 0;
        foreach ($items as $item) {
            $goods = $itemModels[$item['item_id']] ?? null;
            if (!$goods) {
                throw new BusinessException("商品不存在: {$item['item_id']}");
            }
            $total += $goods->getPrice($item['spec_id'] ?? 0) * $item['quantity'];
        }
        return $total;
    }

    private function processCoupon($userId, $couponId, $orderAmount)
    {
        $userCoupon = $this->userCouponRepository->findByUserAndCoupon(
            (int) $userId, (int) $couponId, UserCoupon::STATUS_UNUSED
        );
        if (!$userCoupon) {
            throw new BusinessException('优惠券不存在或已使用');
        }
        if (!$userCoupon->canUse()) {
            throw new BusinessException('优惠券不可用');
        }
        $discount = $userCoupon->coupon->calculateDiscount($orderAmount);
        if ($discount <= 0) {
            throw new BusinessException('优惠券不满足使用条件');
        }
        return $discount;
    }

    private function calculateShippingFee(array $items, $addressId)
    {
        // @roadmap 运费规则计算待接入 DeliveryRuleService，当前返回固定运费
        return 10.00;
    }

    private function createOrderItems($orderId, array $items, $itemModels)
    {
        foreach ($items as $item) {
            $goods = $itemModels[$item['item_id']] ?? null;
            if (!$goods) {
                throw new BusinessException("商品不存在: {$item['item_id']}");
            }
            $price = $goods->getPrice($item['spec_id'] ?? 0);
            $quantity = (int) $item['quantity'];

            $this->orderItemRepository->create([
                'order_id'        => $orderId,
                'item_id'         => $item['item_id'],
                'name'            => $goods->name,
                'image'           => $goods->getMainImageAttribute(),
                'item_no'         => $goods->item_no ?? '',
                'spec_type'       => $goods->spec_type ?? 0,
                'spec_sku_id'     => $item['spec_key'] ?? '',
                'item_attr'       => $item['spec_key_name'] ?? '',
                'item_sku_id'     => $item['spec_id'] ?? 0,
                'content'         => $goods->description ?? '',
                'item_price'      => $price,
                'line_price'      => $goods->market_price ?? 0,
                'total_num'       => $quantity,
                'total_price'     => $price * $quantity,
                'total_pay_price' => $price * $quantity,
                'give_integral'   => $goods->give_integral ?? 0,
                'user_id'         => $item['user_id'] ?? 0,
                'app_id'          => $goods->app_id,
                'created_at'      => time(),
                'updated_at'      => time(),
            ]);
        }
    }

    private function createOrderAddress($orderId, $addressId, $appId)
    {
        $userAddress = $this->userAddressRepository->findOrFail($addressId);

        $this->orderAddressRepository->create([
            'order_id'    => $orderId,
            'name'        => $userAddress->name,
            'phone'       => $userAddress->phone,
            'province_id' => $userAddress->province_id,
            'city_id'     => $userAddress->city_id,
            'region_id'   => $userAddress->district_id,
            'detail'      => $userAddress->detail,
            'postal_code' => $userAddress->zip_code,
            'is_default'  => $userAddress->is_default,
            'user_id'     => $userAddress->user_id,
            'app_id'      => $appId,
            'created_at'  => time(),
            'updated_at'  => time(),
        ]);
    }

    private function reduceStock(array $items, $itemModels)
    {
        foreach ($items as $item) {
            $goods = $itemModels[$item['item_id']] ?? null;
            if (!$goods) {
                throw new BusinessException("商品不存在: {$item['item_id']}");
            }
            $goods->reduceStock($item['quantity'], $item['spec_id'] ?? 0, '订单出库');
        }
    }

    private function restoreStock($orderGoods)
    {
        foreach ($orderGoods as $orderGood) {
            $goods = $this->itemService->findOrFail($orderGood->item_id);
            $goods->addStock($orderGood->total_num, $orderGood->item_sku_id ?? 0, '订单取消恢复库存');
        }
    }

    private function restoreCoupon($userId, $couponId)
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

    private function addSales($orderGoods)
    {
        foreach ($orderGoods as $orderGood) {
            $goods = $this->itemService->findOrFail($orderGood->item_id);
            $goods->addSales($orderGood->total_num);
        }
    }

    private function giveIntegral($order)
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

    // ============================================================
    // 后台 / 报表 用例
    // ============================================================

    public function getTrackingOrders($appId, $pageSize = 20)
    {
        return $this->repository->getTrackingOrders((int) $appId, (int) $pageSize);
    }

    public function getCount($appId): int
    {
        return $this->repository->countByApp((int) $appId);
    }

    public function getTodayOrderCount($appId): int
    {
        return $this->repository->countTodayByApp((int) $appId);
    }

    public function getMonthlyOrderReport($appId): array
    {
        return $this->repository->getMonthlyDailyCounts((int) $appId);
    }

    public function getStatusCounts($appId): array
    {
        return $this->repository->getStatusCounts((int) $appId);
    }

    public function getSalesTrendData(int $days)
    {
        return $this->repository->getDailySalesTrend($days);
    }

    /**
     * 客户洞察指标（含衍生比率：avgOrderValue / repeatRate）
     */
    public function getCustomerInsights(int $appId)
    {
        $stats = $this->repository->getCustomerInsightStats($appId);

        $totalOrders = (int) $stats['total_orders'];
        $totalRevenue = (float) $stats['total_revenue'];
        $totalCustomers = (int) $stats['total_customers'];
        $repeatCustomers = (int) $stats['repeat_customers'];

        return array_merge($stats, [
            'average_order_value' => $totalOrders > 0 ? round($totalRevenue / $totalOrders, 2) : 0.0,
            'repeat_customer_rate' => $totalCustomers > 0 ? round($repeatCustomers / $totalCustomers * 100, 1) : 0.0,
        ]);
    }

    /**
     * 财务统计（透传 Repository，再补占位字段）
     */
    public function getFinanceStatistics($appId)
    {
        $stats = $this->repository->getPaidRevenueStats((int) $appId);

        return [
            'revenue_total'           => $stats['revenue_total'],
            'today_revenue'           => $stats['today_revenue'],
            'month_revenue'           => $stats['month_revenue'],
            'subscription_revenue'    => 0,
            'order_revenue'           => $stats['revenue_total'],
            'total_withdraw'          => 0,
            'earning_per_months'      => $stats['monthly'],
            'monthly_payment_charge'  => [],
            'monthly_withdraw_charge' => [],
        ];
    }

    /**
     * 销售报表（按日统计）
     */
    public function getSalesReport($appId, $startTs = null, $endTs = null): array
    {
        $days = 30;
        if ($startTs && $endTs) {
            $days = min(365, max(1, intval(($endTs - $startTs) / 86400) + 1));
        }

        $base = $this->repository->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $dayStart = strtotime($date . ' 00:00:00');
            $dayEnd = strtotime($date . ' 23:59:59');

            $dayQuery = (clone $base)->whereBetween('created_at', [$dayStart, $dayEnd]);
            $orderCount = (clone $dayQuery)->count();
            $salesAmount = (clone $dayQuery)->where('pay_status', 1)->sum('pay_price');
            $refundAmount = (clone $dayQuery)->where('status', 5)->sum('pay_price');

            $data[] = [
                'date' => $date,
                'order_count' => $orderCount,
                'sales_amount' => round((float) $salesAmount, 2),
                'refund_amount' => round((float) $refundAmount, 2),
            ];
        }

        return $data;
    }

    public function getLatestOrders($appId, $limit = 20)
    {
        return $this->repository->getLatestOrders((int) $appId, (int) $limit);
    }

    public function getStatistics(int $appId): array
    {
        $todayOrders = $this->repository->countTodayByApp($appId);
        $yesterdayOrders = $this->repository->countYesterdayByApp($appId);
        $todayDiff = $todayOrders - $yesterdayOrders;
        $todayGrowth = $yesterdayOrders > 0 ? round(($todayOrders / $yesterdayOrders - 1) * 100, 1) : 0;

        $pendingPayment = $this->repository->countPendingPayment($appId);
        $totalOrders = $this->repository->countByApp($appId);
        $pendingPaymentPct = $totalOrders > 0 ? round($pendingPayment / $totalOrders * 100, 1) : 0;

        $pendingShip = $this->repository->countPendingShip($appId);
        $overdueUnshipped = $this->repository->countOverdueUnshipped($appId);

        $todayRevenue = $this->repository->getTodayRevenue($appId);
        $yesterdayRevenue = $this->repository->getYesterdayRevenue($appId);
        $revenueGrowth = $yesterdayRevenue > 0 ? round(($todayRevenue / $yesterdayRevenue - 1) * 100, 1) : 0;
        $paidTodayCount = $todayOrders > 0 ? $this->repository->countTodayByApp($appId) : 0;
        $avgOrderValue = $paidTodayCount > 0 ? round($todayRevenue / $paidTodayCount, 2) : 0;

        $pendingRefund = $this->repository->countPendingRefund($appId);

        return [
            'todayOrders' => $todayOrders,
            'todayDiff' => $todayDiff,
            'todayGrowth' => $todayGrowth,
            'pendingPayment' => $pendingPayment,
            'pendingPaymentPct' => $pendingPaymentPct,
            'avgStayMin' => 0,
            'pendingShip' => $pendingShip,
            'urgentShip' => $pendingShip,
            'overdueUnshipped' => $overdueUnshipped,
            'todayRevenue' => $todayRevenue,
            'revenueGrowth' => $revenueGrowth,
            'avgOrderValue' => $avgOrderValue,
            'pendingRefund' => $pendingRefund,
        ];
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

    public function updateOrderItemStatus($id, $status)
    {
        $orderItem = $this->orderItemRepository->find($id);
        if (!$orderItem) {
            return false;
        }
        $orderItem->status = $status;
        $orderItem->save();
        return true;
    }

    public function adminDeleteOrder($id)
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

    // ============================================================
    // 积分兑换 & 秒杀订单
    // ============================================================

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
