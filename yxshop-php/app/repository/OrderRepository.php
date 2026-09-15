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
            ->with(['items.item.images', 'address.province', 'address.city', 'address.district', 'delivery', 'coupon']);
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
            ->with(['items', 'user', 'address.province', 'address.city', 'address.district', 'delivery', 'logs' => function($q) { $q->orderBy('created_at', 'desc'); }])
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
    public function getUserOrderStats($userId, $appId = 0)
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
    public function getSalesStats($startTime = null, $endTime = null, $appId = 0)
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

    /**
     * 指定状态订单计数
     */
    public function getOrderCountByStatus(int $appId, $status): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', $status)
            ->count();
    }

    /**
     * 多状态计数（后台首页指标）
     * @return array{pending,cancelled,completed,unpaid,paid,unshipped,shipped: int}
     */
    public function getStatusCounts(int $appId): array
    {
        $base = $this->query()->where('app_id', $appId);
        return [
            'pending'    => (clone $base)->where('status', 10)->count(),
            'cancelled'  => (clone $base)->where('status', 20)->count(),
            'completed'  => (clone $base)->where('status', 30)->count(),
            'unpaid'     => (clone $base)->where('pay_status', 10)->count(),
            'paid'       => (clone $base)->where('pay_status', 20)->count(),
            'unshipped'  => (clone $base)->where('delivery_status', 10)->count(),
            'shipped'    => (clone $base)->where('delivery_status', 20)->count(),
        ];
    }

    /**
     * 总订单数
     */
    public function countByApp(int $appId): int
    {
        return $this->query()->where('app_id', $appId)->count();
    }

    public function countYesterdayByApp(int $appId): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('created_at', '>=', strtotime('yesterday'))
            ->where('created_at', '<', strtotime('today'))
            ->count();
    }

    public function countPendingPayment(int $appId): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', Order::ORDER_STATUS_PENDING)
            ->where('pay_status', Order::PAY_STATUS_UNPAID)
            ->count();
    }

    public function countPendingShip(int $appId): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', Order::ORDER_STATUS_PENDING)
            ->where('pay_status', Order::PAY_STATUS_PAID)
            ->where('delivery_status', Order::DELIVERY_STATUS_UNSHIPPED)
            ->count();
    }

    public function countOverdueUnshipped(int $appId, int $hours = 48): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', Order::ORDER_STATUS_PENDING)
            ->where('pay_status', Order::PAY_STATUS_PAID)
            ->where('delivery_status', Order::DELIVERY_STATUS_UNSHIPPED)
            ->where('pay_time', '<', time() - $hours * 3600)
            ->count();
    }

    public function getTodayRevenue(int $appId): float
    {
        return (float) $this->query()
            ->where('app_id', $appId)
            ->where('pay_status', Order::PAY_STATUS_PAID)
            ->where('pay_time', '>=', strtotime('today'))
            ->sum('pay_price');
    }

    public function getYesterdayRevenue(int $appId): float
    {
        return (float) $this->query()
            ->where('app_id', $appId)
            ->where('pay_status', Order::PAY_STATUS_PAID)
            ->where('pay_time', '>=', strtotime('yesterday'))
            ->where('pay_time', '<', strtotime('today'))
            ->sum('pay_price');
    }

    public function countPendingRefund(int $appId): int
    {
        return \app\model\AfterSales::where('app_id', $appId)
            ->where('status', 10)
            ->count();
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
     * 今日订单数
     */
    public function countTodayByApp(int $appId): int
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('created_at', '>=', strtotime('today'))
            ->count();
    }

    /**
     * 月度日订单数（本月每天的下单量）
     * @return array [{date, order_count}, ...]
     */
    public function getMonthlyDailyCounts(int $appId): array
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('created_at', '>=', strtotime('first day of this month'))
            ->selectRaw("DATE_FORMAT(FROM_UNIXTIME(created_at), '%Y-%m-%d') as date, count(*) as order_count")
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * 按年统计订单数
     * @return array<int|string, array{year:int, total:int}>
     */
    public function getOrderCountByYear(int $appId): array
    {
        return $this->query()
            ->where('app_id', $appId)
            ->selectRaw("YEAR(FROM_UNIXTIME(created_at)) as year, COUNT(*) as total")
            ->groupBy('year')
            ->orderBy('year')
            ->get()
            ->keyBy('year')
            ->toArray();
    }

    /**
     * 日销售趋势（近 N 天）
     * @return array<string, array{date,total,count}>
     */
    public function getDailySalesTrend(int $days, ?int $appId = null): array
    {
        $q = $this->query()
            ->selectRaw("FROM_UNIXTIME(created_at, '%Y-%m-%d') as date, SUM(pay_price) as total, COUNT(*) as count")
            ->where('created_at', '>=', time() - $days * 86400);

        if ($appId !== null && $appId > 0) {
            $q->where('app_id', $appId);
        }

        return $q->groupBy('date')->orderBy('date')->get()->keyBy('date')->toArray();
    }

    /**
     * 客户洞察聚合（原始指标，衍生比率由 Service 计算）
     * @return array{total_orders,total_revenue,repeat_customers,total_customers: int|float}
     */
    public function getCustomerInsightStats(int $appId): array
    {
        $base = $this->query()->where('app_id', $appId);

        return [
            'total_orders' => (clone $base)->count(),
            'total_revenue' => (float) (clone $base)->sum('pay_price'),
            'repeat_customers' => (clone $base)
                ->selectRaw('user_id, COUNT(*) as order_count')
                ->groupBy('user_id')
                ->having('order_count', '>', 1)
                ->get()
                ->count(),
            'total_customers' => (clone $base)->distinct('user_id')->count('user_id'),
        ];
    }

    /**
     * 已支付订单营收聚合（总/今日/月，月度分布）
     * @return array{revenue_total,today_revenue,month_revenue: float, monthly: array}
     */
    public function getPaidRevenueStats(int $appId): array
    {
        $todayStart = strtotime('today');
        $monthStart = strtotime('first day of this month');

        $paidBase = $this->query()
            ->where('app_id', $appId)
            ->where('pay_status', Order::PAY_STATUS_PAID);

        return [
            'revenue_total' => (float) (clone $paidBase)->sum('pay_price'),
            'today_revenue' => (float) (clone $paidBase)->where('created_at', '>=', $todayStart)->sum('pay_price'),
            'month_revenue' => (float) (clone $paidBase)->where('created_at', '>=', $monthStart)->sum('pay_price'),
            'monthly' => (clone $paidBase)
                ->selectRaw("DATE_FORMAT(FROM_UNIXTIME(created_at), '%Y-%m') as month, SUM(pay_price) as total")
                ->groupBy('month')->orderBy('month')
                ->pluck('total', 'month')->toArray(),
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
}
