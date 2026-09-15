<?php

namespace app\admin\controller;

use support\Request;
use support\Response;
use app\service\OrderService;
use app\service\UserService;
use app\service\ItemService;
use app\service\CategoryService;
use app\service\DashboardService;

class IndexController extends BaseController
{
    protected $orderService;
    protected $userService;
    protected $itemService;
    protected $categoryService;
    protected $dashboardService;

    public function __construct()
    {
        parent::__construct();
        $this->orderService = new OrderService();
        $this->userService = new UserService();
        $this->itemService = new ItemService();
        $this->categoryService = new CategoryService();
        $this->dashboardService = new DashboardService();
    }

    public function statistics(Request $request): Response
    {
        $appId = $this->getAppId();
        $financeStats = $this->orderService->getFinanceStatistics($appId);
        $productReport = $this->itemService->getProductReport($appId);
        $orderStats = $this->orderService->getOrderStats($appId);
        $statusCounts = $this->orderService->getStatusCounts($appId);

        $trafficSources = $this->dashboardService->getTrafficSources($appId);
        $genderRatio = $this->dashboardService->getGenderRatio($appId);
        $regionData = $this->dashboardService->getRegionData($appId);
        $customerTrend = $this->dashboardService->getCustomerTrend($appId);

        $regionTotal = array_sum(array_column($regionData, 'value'));

        $returningUsers = $this->dashboardService->getReturningUsers($appId);

        $prevReturning = $this->dashboardService->getReturningUsersPrevPeriod($appId);

        $returningTrend = 0;
        if ($prevReturning > 0) {
            $returningTrend = round(($returningUsers - $prevReturning) / $prevReturning * 100, 1);
        }

        return $this->success([
            'total_payment' => $financeStats['revenue_total'] ?? 0,
            'subscription_earning' => $financeStats['subscription_revenue'] ?? 0,
            'order_payment' => $financeStats['order_revenue'] ?? 0,
            'total_withdraw' => $financeStats['total_withdraw'] ?? 0,
            'physical_product' => $productReport['physical_product'] ?? 0,
            'digital_product' => $productReport['digital_product'] ?? 0,
            'total_customer' => $this->userService->getCount($appId),
            'total_seller' => 0,
            'inhouse_order' => $orderStats['inhouse_order'] ?? 0,
            'delivered_order' => $statusCounts['completed'] ?? 0,
            'shipped_order' => $statusCounts['shipped'] ?? 0,
            'cancel_order' => $statusCounts['cancelled'] ?? 0,
            'digital_order' => $orderStats['digital_order'] ?? 0,
            'today_orders' => $this->orderService->getTodayOrderCount($appId),
            'low_stock_products' => $productReport['low_stock'] ?? 0,
            'sales_trend' => $financeStats['revenue_trend'] ?? 0,
            'orders_trend' => $financeStats['order_trend'] ?? 0,
            'new_users_trend' => 0,
            'traffic_sources' => $trafficSources,
            'gender_male' => $genderRatio['male'],
            'gender_female' => $genderRatio['female'],
            'region_data' => $regionData,
            'region_total' => $regionTotal,
            'region_trend' => 0,
            'customer_trend' => $customerTrend,
            'returning_users' => $returningUsers,
            'returning_trend' => $returningTrend,
        ]);
    }

    public function charts(Request $request): Response
    {
        $appId = $this->getAppId();
        $financeStats = $this->orderService->getFinanceStatistics($appId);

        return $this->success([
            'monthly_order_report' => $this->orderService->getMonthlyOrderReport($appId),
            'order_by_year' => $this->orderService->getOrderByYear($appId),
            'product_by_year' => $this->itemService->getProductByYear($appId),
            'product_sell_by_month' => $this->itemService->getProductSellByMonth($appId),
            'earning_per_months' => $financeStats['earning_per_months'] ?? [],
            'monthly_payment_charge' => $financeStats['monthly_payment_charge'] ?? [],
            'monthly_withdraw_charge' => $financeStats['monthly_withdraw_charge'] ?? [],
            'web_visitors' => [],
        ]);
    }

    public function recentOrders(Request $request): Response
    {
        $appId = $this->getAppId();
        $limit = min((int)$request->get('limit', 20), 100);
        $orders = $this->orderService->getLatestOrders($appId, $limit);
        return $this->success($orders);
    }

    public function recentCustomers(Request $request): Response
    {
        $appId = $this->getAppId();
        $limit = min((int)$request->get('limit', 10), 50);
        $users = $this->userService->getLatestUsers($appId, $limit);
        $list = [];
        foreach ($users as $u) {
            $list[] = [
                'id' => $u->id,
                'name' => $u->nickname ?: '',
                'nickname' => $u->nickname ?: '',
                'avatar' => $u->avatar_url ?: '',
                'location' => trim(($u->province ?: '') . ($u->city ? ' ' . $u->city : '')) ?: '-',
                'created_at' => $u->created_at,
            ];
        }
        return $this->success($list);
    }
}
