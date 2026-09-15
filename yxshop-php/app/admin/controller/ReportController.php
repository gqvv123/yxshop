<?php

namespace app\admin\controller;

use support\Request;
use app\service\UserService;
use app\service\OrderService;
use app\service\ItemService;

class ReportController extends BaseController
{
    protected $userService;
    protected $orderService;
    protected $itemService;

    public function __construct()
    {
        parent::__construct();
        $this->userService = new UserService();
        $this->orderService = new OrderService();
        $this->itemService = new ItemService();
    }

    public function users(Request $request)
    {
        $appId = $this->getAppId($request);
        $startDate = trim($request->get('start_date', ''));
        $endDate = trim($request->get('end_date', ''));
        $startTs = $startDate ? strtotime($startDate . ' 00:00:00') : null;
        $endTs = $endDate ? strtotime($endDate . ' 23:59:59') : null;

        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);

        $allData = $this->userService->getUserReport($appId, $startTs, $endTs);
        $total = count($allData);
        $offset = ($page - 1) * $pageSize;
        $list = array_slice($allData, $offset, $pageSize);

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function sales(Request $request)
    {
        $appId = $this->getAppId($request);
        $startDate = trim($request->get('start_date', ''));
        $endDate = trim($request->get('end_date', ''));
        $startTs = $startDate ? strtotime($startDate . ' 00:00:00') : null;
        $endTs = $endDate ? strtotime($endDate . ' 23:59:59') : null;

        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);

        $allData = $this->orderService->getSalesReport($appId, $startTs, $endTs);
        $total = count($allData);
        $offset = ($page - 1) * $pageSize;
        $list = array_slice($allData, $offset, $pageSize);

        return $this->success(['list' => $list, 'total' => $total]);
    }

    public function products(Request $request)
    {
        $appId = $this->getAppId($request);

        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);

        $allData = $this->itemService->getProductReportList($appId);
        $total = count($allData);
        $offset = ($page - 1) * $pageSize;
        $list = array_slice($allData, $offset, $pageSize);

        return $this->success(['list' => $list, 'total' => $total]);
    }
}
