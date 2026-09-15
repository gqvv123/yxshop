<?php

namespace app\admin\controller;

use support\Request;
use support\Response;
use app\service\PromOrderService;
use app\service\NotificationSendService;
use app\service\MarketingService;

class MarketingController extends BaseController
{
    protected $promOrderService;
    protected $notificationSendService;
    protected $marketingService;

    public function __construct()
    {
        parent::__construct();
        $this->promOrderService = new PromOrderService();
        $this->notificationSendService = new NotificationSendService();
        $this->marketingService = new MarketingService();
    }

    public function promotions(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $promotions = $this->promOrderService->getPaginatedList($appId, $pageSize);
        return $this->paginate($promotions);
    }

    public function notifications(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $notifications = $this->notificationSendService->getPaginatedList($appId, $pageSize);
        return $this->paginate($notifications);
    }

    public function summary(Request $request): Response
    {
        $appId = $this->getAppId($request);
        $data = $this->marketingService->getSummary($appId);
        return $this->success($data);
    }

    public function statistics(Request $request): Response
    {
        $appId = $this->getAppId($request);
        $data = $this->marketingService->getStatistics($appId);
        return $this->success($data);
    }

    public function campaigns(Request $request): Response
    {
        $appId = $this->getAppId($request);
        $params = $request->get();
        $data = $this->marketingService->getCampaigns($appId, $params);
        return $this->success($data);
    }

    public function coupons(Request $request): Response
    {
        $appId = $this->getAppId($request);
        $params = $request->get();
        $data = $this->marketingService->getCoupons($appId, $params);
        return $this->success($data);
    }

    public function channels(Request $request): Response
    {
        $appId = $this->getAppId($request);
        $data = $this->marketingService->getChannels($appId);
        return $this->success($data);
    }

    public function insights(Request $request): Response
    {
        $appId = $this->getAppId($request);
        $data = $this->marketingService->getInsights($appId);
        return $this->success($data);
    }

    public function export(Request $request): Response
    {
        $appId = $this->getAppId($request);
        $filepath = $this->marketingService->exportData($appId);

        if (!file_exists($filepath)) {
            return $this->error('导出文件生成失败');
        }

        $filename = basename($filepath);
        return new Response(200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ], file_get_contents($filepath));
    }
}
