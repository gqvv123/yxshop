<?php

namespace app\service;

use app\repository\UserRepository;
use app\repository\ItemRepository;
use app\repository\OrderRepository;
use app\repository\PaymentRepository;
use Exception;

/**
 * 市场数据服务（聚合多 Repository 的协调者，无主 Repository）
 *
 * 修复了原文件的 2 处字段脱节 bug：
 *   - 用 `payment_status=1` 判断付款成功（错；Payment::STATUS_SUCCESS=20）
 *   - 用 `order_status>=2` 判断已付款（错；Order 字段名是 status）
 * 现统一委托到 PaymentRepository::sumPaidAmount / OrderRepository 状态计数。
 */
class MarketService
{
    protected UserRepository $userRepository;
    protected ItemRepository $itemRepository;
    protected OrderRepository $orderRepository;
    protected PaymentRepository $paymentRepository;

    public function __construct()
    {
        $this->userRepository    = new UserRepository();
        $this->itemRepository    = new ItemRepository();
        $this->orderRepository   = new OrderRepository();
        $this->paymentRepository = new PaymentRepository();
    }

    /**
     * 首页核心指标
     */
    public function getHomeData($appId = 0)
    {
        try {
            $todayStart = strtotime(date('Y-m-d'));

            return [
                'user_count'        => $this->userRepository->countByApp((int) $appId),
                'item_count'        => $this->itemRepository->countByApp((int) $appId),
                'order_count'       => $this->orderRepository->countByApp((int) $appId),
                'today_order_count' => $this->orderRepository->countTodayByApp((int) $appId),
                'total_sales'       => $this->paymentRepository->sumPaidAmount((int) $appId),
                'today_sales'       => $this->paymentRepository->sumPaidAmount((int) $appId, $todayStart),
            ];
        } catch (Exception $e) {
            \support\Log::error('获取首页数据失败', ['app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 销售统计（指定时间窗口，已付款订单口径）
     */
    public function getStats($startTime = null, $endTime = null, $appId = 0)
    {
        try {
            return $this->orderRepository->getSalesStats($startTime, $endTime, (int) $appId);
        } catch (Exception $e) {
            \support\Log::error('获取统计数据失败',
                compact('startTime', 'endTime', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getWebVisitors($appId)
    {
        return [];
    }
}
