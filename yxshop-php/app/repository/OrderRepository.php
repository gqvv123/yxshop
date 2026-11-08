<?php

namespace app\repository;

use app\model\Order;

/**
 * 订单仓储类
 *
 * 设计原则（4 层架构）：
 *   - 所有查询语义封装为「意图揭示方法」，Service 调用 `$repo->getXxx($args)` 而非 `$repo->query()->where(...)`
 *   - 仅返回 Model / Collection / 简单数组，不抛业务异常
 *   - deleted_at 软删除已由 BaseModel 全局 scope 自动过滤
 */
class OrderRepository extends BaseRepository
{
    use OrderStatisticRepositoryTrait;

    protected $model = Order::class;

    // ---------------- 单订单查询 ----------------

    /**
     * 根据订单号查找订单
     */
    public function findByOrderNo($orderNo)
    {
        return $this->query()->where('order_no', $orderNo)->first();
    }

    /**
     * 按订单号查并加行锁（用于支付回调等并发场景）
     */
    public function findByOrderNoForUpdate(string $orderNo)
    {
        return $this->query()
            ->where('order_no', $orderNo)
            ->lockForUpdate()
            ->first();
    }

    /**
     * 根据 ID 查订单（含关联），用户校验可选
     * @return Order|null
     */
    public function findOrderForUser(int $id, ?int $userId = null)
    {
        $q = $this->query()->where('id', $id);
        if ($userId !== null) {
            $q->where('user_id', $userId);
        }
        return $q->first();
    }

    /**
     * 订单详情（含商品/地址/物流/优惠券预加载），用户校验可选
     * @return Order|null
     */
    public function findOrderWithRelations(int $id, ?int $userId = null)
    {
        $q = $this->query()
            ->where('id', $id)
            ->with(['items.item.images', 'address.provinceRegion', 'address.cityRegion', 'address.districtRegion', 'delivery', 'coupon']);
        if ($userId !== null) {
            $q->where('user_id', $userId);
        }
        return $q->first();
    }

    /**
     * 后台订单详情（含商品/用户/地址预加载）
     */
    public function findAdminOrderDetail($id)
    {
        return $this->query()
            ->with(['items', 'user', 'address.provinceRegion', 'address.cityRegion', 'address.districtRegion', 'delivery', 'logs' => function($q) { $q->orderBy('created_at', 'desc'); }])
            ->find($id);
    }

    // ---------------- 列表 / 分页 ----------------

    /**
     * 用户订单列表（含分页元数据）
     * @return array{data: \Illuminate\Support\Collection, total: int, page: int, page_size: int}
     */
    public function getOrdersByUser(int $userId, int $page = 1, int $pageSize = 20, $status = null): array
    {
        $base = $this->query()
            ->where('user_id', $userId)
            ->with(['items', 'address']);

        if ($status !== null) {
            $base->where('status', $status);
        }

        $total = (clone $base)->count();
        $offset = ($page - 1) * $pageSize;
        $items = $base->orderBy('id', 'desc')->offset($offset)->limit($pageSize)->get();

        return [
            'data' => $items,
            'total' => $total,
            'page' => $page,
            'page_size' => $pageSize,
        ];
    }

    /**
     * 配送中订单（status >= 2，含用户/商品）
     */
    public function getTrackingOrders(int $appId, int $pageSize = 20)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', '>=', 2)
            ->with(['user', 'items'])
            ->orderBy('id', 'desc')
            ->paginate($pageSize);
    }

    /**
     * 最新订单（含用户/商品）
     */
    public function getLatestOrders(int $appId, int $limit = 20)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->with(['user', 'items'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * 后台订单列表（带搜索/状态/日期筛选）
     */
    public function getAdminOrderList(int $appId, array $params = [], int $pageSize = 20)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if (isset($params['status']) && $params['status'] !== '' && $params['status'] !== null) {
            $query->where('status', $params['status']);
        }

        if (isset($params['pay_status']) && $params['pay_status'] !== '' && $params['pay_status'] !== null) {
            $query->where('pay_status', $params['pay_status']);
        }

        if (isset($params['delivery_status']) && $params['delivery_status'] !== '' && $params['delivery_status'] !== null) {
            $query->where('delivery_status', $params['delivery_status']);
        }

        if (!empty($params['start_date'])) {
            $query->where('created_at', '>=', strtotime($params['start_date']));
        }

        if (!empty($params['end_date'])) {
            $query->where('created_at', '<=', strtotime($params['end_date'] . ' 23:59:59'));
        }

        if (!empty($params['search'])) {
            $search = $params['search'];
            $query->where(function ($q) use ($search) {
                $q->where('order_no', 'like', "%{$search}%")
                  ->orWhere('user_id', $search);
            });
        }

        if (!empty($params['date'])) {
            // 支持 "2026-06-01 to 2026-06-30" 区间或单日期
            $dates = explode(' to ', $params['date']);
            if (count($dates) === 2) {
                $query->whereBetween('created_at', [strtotime($dates[0]), strtotime($dates[1] . ' 23:59:59')]);
            } else {
                $query->whereDate('created_at', $params['date']);
            }
        }

        return $query->with(['items', 'user', 'address'])
            ->orderBy('id', 'desc')
            ->paginate($pageSize);
    }

    /**
     * 事务流水分页
     */
    public function getTransactionList(int $appId = 0, int $page = 1, int $limit = 20, $status = '', $startTime = null, $endTime = null)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if ($status !== '' && $status !== null) {
            $query->where('status', $status);
        }
        if ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }
        if ($endTime) {
            $query->where('created_at', '<=', $endTime);
        }
        return $query->paginate($limit, ['*'], 'page', $page);
    }

    // ---------------- 统计 / 报表 ----------------

    /**
     * 用户订单状态统计
     */
    public function getUserOrderStats($userId, $appId = 0): array
    {
        $base = $this->query()->where('user_id', $userId);

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', Order::ORDER_STATUS_PENDING)->count(),
            'completed' => (clone $base)->where('status', Order::ORDER_STATUS_COMPLETE)->count(),
            'cancelled' => (clone $base)->where('status', Order::ORDER_STATUS_CANCEL)->count(),
        ];
    }

    /**
     * 销售统计（按时间窗口）
     */
    public function getSalesStats($startTime = null, $endTime = null, $appId = 0): array
    {
        $query = $this->query()
            ->where('status', Order::ORDER_STATUS_COMPLETE)
            ->where('pay_status', Order::PAY_STATUS_PAID);

        if ($startTime) $query->where('created_at', '>=', $startTime);
        if ($endTime)   $query->where('created_at', '<=', $endTime);
        if ($appId > 0) $query->where('app_id', $appId);

        return [
            'order_count' => (clone $query)->count(),
            'total_amount' => (clone $query)->sum('pay_price'),
            'avg_amount' => (clone $query)->avg('pay_price'),
        ];
    }

    /**
     * 待处理订单
     */
    public function getPendingOrders($appId = 0)
    {
        $query = $this->query()
            ->where('status', Order::ORDER_STATUS_PENDING)
            ->with(['user', 'items']);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 超时未支付订单
     */
    public function getTimeoutOrders($timeout = 1800)
    {
        return $this->query()
            ->where('status', Order::ORDER_STATUS_PENDING)
            ->where('pay_status', Order::PAY_STATUS_UNPAID)
            ->where('created_at', '<', time() - $timeout)
            ->get();
    }











    public function getOrderDeliveries(int $orderId)
    {
        return \app\model\OrderDelivery::where('order_id', $orderId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getUserOrderStatsForProfile(int $userId, int $appId): array
    {
        $base = $this->query()->where('user_id', $userId)->where('app_id', $appId);
        return [
            'totalSpent' => (float) (clone $base)->where('pay_status', Order::PAY_STATUS_PAID)->sum('pay_price'),
            'orderCount' => (clone $base)->count(),
            'returnCount' => \app\model\AfterSales::where('user_id', $userId)->where('app_id', $appId)->where('status', 30)->count(),
        ];
    }







    /**
     * 积分兑换订单分页（用户端）
     */
    public function getIntegralOrdersByUser(int $userId, int $page = 1, int $pageSize = 20)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('is_integral', 1)
            ->with(['items'])
            ->orderBy('created_at', 'desc');

        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 秒杀订单分页（用户端，promotion_type=1）
     */
    public function getSeckillOrdersByUser(int $userId, int $page = 1, int $pageSize = 20)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('promotion_type', 1)
            ->with(['items' => function ($q) {
                $q->with(['item' => function ($iq) {
                    $iq->with(['images' => function ($iiq) {
                        $iiq->orderBy('is_main', 'desc')->orderBy('sort', 'asc');
                    }]);
                }]);
            }])
            ->orderBy('id', 'desc');

        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 用户订单数
     */
    public function countByUser(int $userId): int
    {
        return $this->query()->where('user_id', $userId)->count();
    }

    /**
     * 用户已完成订单消费总额
     */
    public function sumCompletedByUser(int $userId): float
    {
        return (float) $this->query()
            ->where('user_id', $userId)
            ->whereIn('status', [Order::ORDER_STATUS_COMPLETE])
            ->sum('total_price');
    }

    /**
     * 创建积分兑换订单
     */
    public function createIntegralOrder(array $orderData, array $itemData)
    {
        $order = $this->create($orderData);

        // 创建订单商品记录
        $itemData['order_id'] = $order->id;
        $itemData['order_no'] = $orderData['order_no'];
        $itemData['created_at'] = time();
        \app\model\OrderItem::create($itemData);

        return $order;
    }

    /**
     * 获取店铺在指定時間段內已完成的订单（企业版分賬结算用）
     *
     * @param string $shopId 店铺ID（0 表示平台自營）
     * @param int $periodStart 周期開始時間戳
     * @param int $periodEnd 周期結束時間戳
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getCompletedOrdersByShop(string $shopId, int $periodStart, int $periodEnd)
    {
        $query = $this->query()
            ->where('status', Order::ORDER_STATUS_COMPLETE)
            ->whereBetween('created_at', [$periodStart, $periodEnd]);

        // 如果 shop_id 字段存在則按店铺过滤，否則返回空集合（开源版無多店铺）
        // 企业版安裝後會添加 shop_id 字段到订单表
        if ($shopId !== '0' && $shopId !== '') {
            $query->where('shop_id', $shopId);
        }

        return $query->get();
    }

    /**
     * 按用户 ID 批量统计订单数与已支付金额，返回以 user_id 为键的数组。
     *
     * 用于消除「循环里逐个 count/sum」的 N+1 查询。
     *
     * @param array<int, mixed> $userIds
     * @param int $paidStatus 已支付状态值
     * @return array<string, array{order_count: int, total_spent: float}>
     */
    public function aggregateByUserIds(array $userIds, int $paidStatus = 20): array
    {
        $userIds = array_values(array_unique(array_filter(array_map('intval', $userIds))));
        if (!$userIds) {
            return [];
        }

        $rows = $this->query()
            ->whereIn('user_id', $userIds)
            ->groupBy('user_id')
            ->selectRaw(
                'user_id, COUNT(*) as order_count, '
                . 'SUM(CASE WHEN pay_status = ? THEN pay_price ELSE 0 END) as total_spent',
                [$paidStatus]
            )
            ->get();

        $map = [];
        foreach ($rows as $row) {
            $map[(string) $row->user_id] = [
                'order_count' => (int) $row->order_count,
                'total_spent' => (float) $row->total_spent,
            ];
        }
        return $map;
    }

}
