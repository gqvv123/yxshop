<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\service\OrderService;
use app\service\ItemService;
use app\service\PaymentService;
use app\service\SettingService;
use app\exception\BusinessException;

class RecommendationController extends BaseController
{
    protected $orderService;
    protected $itemService;
    protected $paymentService;
    protected $settingService;

    public function __construct()
    {
        $this->orderService = new OrderService();
        $this->itemService = new ItemService();
        $this->paymentService = new PaymentService();
        $this->settingService = new SettingService();
    }

    public function salesTrend(Request $request): Response
    {
        $days = min((int)$request->get('days', 7), 90);

        $sales = $this->orderService->getSalesTrendData($days);

        $salesData = [];
        $ordersData = [];
        $dates = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dates[] = date('m-d', strtotime($date));
            $salesData[] = isset($sales[$date]) ? (float)$sales[$date]->total : 0;
            $ordersData[] = isset($sales[$date]) ? (int)$sales[$date]->count : 0;
        }

        return $this->success([
            'dates' => $dates,
            'sales' => $salesData,
            'orders' => $ordersData,
        ]);
    }

    public function hotDishes(Request $request): Response
    {
        $limit = min((int)$request->get('limit', 10), 50);
        $items = $this->orderService->getHotItems($limit);
        return $this->success($items);
    }

    public function customerInsights(Request $request): Response
    {
        $appId = $this->getAppId($request);

        // 业务指标计算（avgOrderValue / repeatRate）已下沉到 Service
        $insights = $this->orderService->getCustomerInsights($appId);
        $insights['payment_methods'] = $this->paymentService->getPaymentMethodStats($appId);

        return $this->success($insights);
    }

    public function strategies(Request $request): Response
    {
        $strategies = $this->settingService->getStrategiesByGroup('recommendation_strategy');
        return $this->success($strategies);
    }

    public function updateStrategy(Request $request): Response
    {
        $data = $request->post();
        $strategyId = $data['strategy_id'] ?? null;

        if (!$strategyId) {
            throw new BusinessException('策略ID不能为空');
        }

        $ok = $this->settingService->updateStrategy(
            (int)$strategyId,
            'recommendation_strategy',
            $data
        );

        if (!$ok) {
            throw new BusinessException('策略不存在');
        }

        return $this->success(null, '策略更新成功');
    }
}
