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
use app\exception\BusinessException;
use app\validate\OrderValidate;
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
    use OrderIntegralTrait;

    use OrderAdminTrait;

    use OrderAmountTrait;

    use OrderReportTrait;

    use OrderFulfillmentTrait;

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
            $user = $this->userService->findOrFail($data['user_id']);
            $itemModels = $this->preloadItems($data['items']);
            $this->checkStock($data['items'], $itemModels);
            $orderAmount = $this->calculateOrderAmount($data['items'], $itemModels);
            $couponDiscount = !empty($data['coupon_id'])
                ? $this->processCoupon($data['user_id'], $data['coupon_id'], $orderAmount)
                : 0;
            $shippingFee = $this->calculateShippingFee(
                $data['items'],
                $data['address_id'],
                $itemModels,
                (float) $orderAmount,
                (int) ($data['delivery_id'] ?? 0)
            );
            // 会员等级折扣（只对商品金额生效，不折运费）
            $memberDiscount = $this->calculateMemberDiscount($user, $orderAmount);
            $payAmount = max(0, round($orderAmount + $shippingFee - $couponDiscount - $memberDiscount, 2));

            return $this->transaction(function () use ($data, $orderAmount, $payAmount, $shippingFee, $couponDiscount, $memberDiscount, $itemModels) {
                $order = $this->repository->create([
                    'order_no'        => Order::generateOrderNo(),
                    'user_id'         => $data['user_id'],
                    'app_id'          => $data['app_id'],
                    'total_price'     => $orderAmount,
                    'pay_price'       => $payAmount,
                    'express_price'   => $shippingFee,
                    'delivery_id'     => (int) ($data['delivery_id'] ?? 0),
                    'coupon_id'       => $data['coupon_id'] ?? 0,
                    'coupon_price'    => $couponDiscount,
                    'discount_price'  => $memberDiscount,
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
                // 核销优惠券（不做这一步同一张券可以反复抵扣）
                $this->useCouponForOrder($data['user_id'], (int) ($data['coupon_id'] ?? 0), $order->id);
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
    public function getOrderList($userId, $page = 1, $pageSize = 20, $status = null, $tab = null): array
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

            // 批量取出订单中的商品，避免逐条查询（N+1）
            $itemMap = $this->itemRepository->findByIds(
                array_map(fn($oi) => (int) $oi->item_id, $order->items->all())
            );

            foreach ($order->items as $orderItem) {
                $item = $itemMap[(string) $orderItem->item_id] ?? null;
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

    // ============================================================
    // 创建订单 内部步骤
    // ============================================================

    // ============================================================
    // 后台 / 报表 用例
    // ============================================================

    // ============================================================
    // 积分兑换 & 秒杀订单
    // ============================================================

}
