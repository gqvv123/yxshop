<?php

namespace app\service;

use app\repository\NotificationSceneRepository;
use app\repository\NotificationSendRepository;
use app\model\SystemLog;
use Exception;

class NotificationEventService
{
    protected NotificationSceneRepository $sceneRepo;
    protected NotificationSendRepository $sendRepo;
    protected $engineService;

    public function __construct()
    {
        $this->sceneRepo = new NotificationSceneRepository();
        $this->sendRepo = new NotificationSendRepository();
        $this->engineService = new NotificationEngineService();
    }

    public function fireEvent($sceneCode, array $variables = [], $recipientId = null, $recipientType = 'user')
    {
        try {
            $scene = $this->sceneRepo->query()
                ->where('scene_code', $sceneCode)->first();
            if (!$scene || !$scene->is_active) {
                return false;
            }

            $rendered = $this->engineService->renderBySceneCode($sceneCode, $variables);

            $send = $this->sendRepo->create([
                'scene_id' => $scene->id,
                'template_id' => $rendered['template_id'] ?? 0,
                'recipient_id' => $recipientId,
                'recipient_type' => $recipientType,
                'send_type' => $rendered['template_type'] ?? 'system',
                'title' => $rendered['title'],
                'content' => $rendered['content'],
                'status' => 10,
                'app_id' => $scene->app_id,
            ]);

            return $send;

        } catch (Exception $e) {
            SystemLog::record(SystemLog::LEVEL_ERROR, '通知事件触发失败: ' . $e->getMessage(), null, '', 0, '', 'notification', '', '', '', '', 0);
            return false;
        }
    }

    public function fireOrderCreated($order) 
    {
        return $this->fireEvent('order_created', [
            'order_no' => $order->order_no ?? '',
            'user_name' => $order->user_name ?? '',
            'total_price' => $order->total_price ?? 0,
        ], $order->user_id ?? null);
    }

    public function fireOrderPaid($order)
    {
        return $this->fireEvent('order_paid', [
            'order_no' => $order->order_no ?? '',
            'pay_price' => $order->pay_price ?? 0,
        ], $order->user_id ?? null);
    }

    public function fireOrderShipped($order)
    {
        return $this->fireEvent('order_shipped', [
            'order_no' => $order->order_no ?? '',
            'express_no' => $order->express_no ?? '',
        ], $order->user_id ?? null);
    }

    public function fireOrderCompleted($order)
    {
        return $this->fireEvent('order_completed', [
            'order_no' => $order->order_no ?? '',
        ], $order->user_id ?? null);
    }

    public function fireRefundSuccess($order, $refundAmount = 0)
    {
        return $this->fireEvent('refund_success', [
            'order_no' => $order->order_no ?? '',
            'refund_amount' => $refundAmount,
        ], $order->user_id ?? null);
    }

    public function fireAfterSalesResult($afterSales, $result)
    {
        return $this->fireEvent('after_sales_result', [
            'order_no' => $afterSales->order_no ?? '',
            'result' => $result,
        ], $afterSales->user_id ?? null);
    }
}
