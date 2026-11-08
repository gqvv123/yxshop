<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\IntegralService;
use app\service\SignService;
use app\service\ItemService;
use app\service\OrderService;
use app\service\UserService;
use app\exception\BusinessException;

/**
 * 积分商城（聚合）
 *
 * 4 层架构：Controller -> Service -> Repository -> Model
 * 控制器禁止直接 use app\model\*
 */
class PointExchangeController extends BaseController
{
    protected $integralService;
    protected $signService;
    protected $itemService;
    protected $orderService;
    protected $userService;

    public function __construct()
    {
        $this->integralService = new IntegralService();
        $this->signService = new SignService();
        $this->itemService = new ItemService();
        $this->orderService = new OrderService();
        $this->userService = new UserService();
    }

    /**
     * 积分商城首页（聚合）
     *
     * GET /api/v1/point/center
     * 返回：积分余额、签到状态、积分商品列表
     */
    public function center(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $appId = (int) $request->get('app_id', 0);

        $integralBalance = 0;
        $signedToday = false;
        $continuousDays = 0;

        if ($userId) {
            $user = $this->userService->find($userId);
            $appId = (int) ($user->app_id ?? 0);
            $integralBalance = (float) ($user->integral ?? 0);
            $signedToday = $this->signService->isUserSignedToday($userId, $appId);
            $continuousDays = $this->signService->getUserContinuousDays($userId, $appId);
        }

        // 积分商品列表
        $items = $this->itemService->getPointExchangeList($appId, 10)
            ->map(function ($item) {
                return $this->formatPointItem($item);
            });

        return $this->success([
            'integral_balance' => $integralBalance,
            'signed_today'     => $signedToday,
            'continuous_days'  => $continuousDays,
            'items'            => $items,
        ]);
    }

    /**
     * 积分商品列表（分页）
     *
     * GET /api/v1/point-item/list?page=1&page_size=20
     */
    public function list(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 20)));

        $result = $this->itemService->getPointExchangeItems($appId, $page, $pageSize);

        $items = collect($result->items())->map(function ($item) {
            return $this->formatPointItem($item);
        })->values();

        return $this->success([
            'list'      => $items,
            'total'     => $result->total(),
            'page'      => $result->currentPage(),
            'page_size' => $result->perPage(),
            'last_page' => $result->lastPage(),
        ]);
    }

    /**
     * 积分兑换商品
     *
     * POST /api/v1/point-item/exchange
     * item_id=1, quantity=1, address_id=1
     */
    public function exchange(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        try {
            $data = $this->orderService->createIntegralExchangeOrder($userId, $request->post());
            return $this->success($data, '兑换成功');
        } catch (BusinessException $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 兑换记录
     *
     * GET /api/v1/point-item/records?page=1&page_size=20
     */
    public function records(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 20)));

        $result = $this->orderService->getIntegralExchangeRecords($userId, $page, $pageSize);

        $list = collect($result->items())->map(function ($order) {
            $firstItem = $order->items->first();
            $itemImage = '';
            if ($firstItem) {
                $item = $this->itemService->find($firstItem->item_id);
                if ($item) {
                    $itemImage = $this->itemService->getMainImage($item);
                }
            }
            return [
                'order_id'    => $order->id,
                'order_no'    => $order->order_no,
                'status'      => $order->status,
                'status_text' => $order->order_status_text ?? '',
                'item_name'   => $firstItem?->item_name ?? '',
                'item_image'  => $itemImage,
                'quantity'    => $firstItem?->quantity ?? 1,
                'points_used' => (int) ($order->total_price ?? 0),
                'created_at'  => $order->created_at,
            ];
        })->values();

        return $this->success([
            'list'      => $list,
            'total'     => $result->total(),
            'page'      => $result->currentPage(),
            'page_size' => $result->perPage(),
            'last_page' => $result->lastPage(),
        ]);
    }

    /**
     * 积分流水
     *
     * GET /api/v1/user/integral-log?type=1&page=1&page_size=20
     */
    public function integralLog(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $type = $request->get('type');
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 20)));

        $result = $this->integralService->getIntegralLog($userId, $type, $page, $pageSize);

        $items = collect($result->items())->map(function ($log) {
            return [
                'id'             => $log->id,
                'amount'         => (int) $log->amount,
                'before_balance' => (int) $log->before_balance,
                'after_balance'  => (int) $log->after_balance,
                'type'           => $log->type,
                'type_text'      => [
                    IntegralService::TYPE_EARN   => '获得',
                    IntegralService::TYPE_SPEND => '消耗',
                    IntegralService::TYPE_EXPIRE=> '过期',
                ][$log->type] ?? '其他',
                'note'           => $log->note ?? '',
                'created_at'     => $log->created_at,
            ];
        })->values();

        return $this->success([
            'list'      => $items,
            'total'     => $result->total(),
            'page'      => $result->currentPage(),
            'page_size' => $result->perPage(),
            'last_page' => $result->lastPage(),
        ]);
    }

    /**
     * 积分统计
     *
     * GET /api/v1/user/integral-stats
     */
    public function integralStats(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $stats = $this->integralService->getIntegralStats($userId);
        $user = $this->userService->find($userId);

        return $this->success([
            'balance'      => (float) ($user->integral ?? 0),
            'freeze'       => (float) ($user->freeze_integral ?? 0),
            'total_earned' => (float) ($stats['total_earned'] ?? 0),
            'total_spent'  => (float) ($stats['total_spent'] ?? 0),
            'month_earned' => $this->integralService->getMonthEarned($userId),
        ]);
    }

    // ============================================================
    // 私有辅助
    // ============================================================

    private function formatPointItem($item): array
    {
        return [
            'id'               => $item->id,
            'name'             => $item->name,
            'subtitle'         => $item->subtitle ?? '',
            'image'            => $this->itemService->getMainImage($item),
            'points_exchange'  => (int) $item->points_exchange,
            'sale_price'       => (float) $item->sale_price,
            'stock'            => (int) $item->stock,
            'is_physical'      => (int) $item->is_physical,
            'exchange_type'    => $item->sale_price > 0 ? 'points_and_money' : 'points_only',
            'exchange_count'   => (int) ($item->sales_volume ?? 0),
        ];
    }
}
