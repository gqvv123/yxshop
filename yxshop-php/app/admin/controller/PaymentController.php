<?php

namespace app\admin\controller;

use support\Request;
use app\service\PaymentService;
use app\service\OrderService;
use app\validate\PaymentValidate;
use app\exception\ValidationException;

class PaymentController extends BaseController
{
    protected $paymentService;

    public function __construct()
    {
        parent::__construct();
        $this->paymentService = new PaymentService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $paymentMethod = $request->get('payment_method', '');
        $status = $request->get('status', '');

        $payments = $this->paymentService->getPaymentList($page, $limit, [
            'payment_method' => $paymentMethod,
            'status' => $status,
            'app_id' => $appId
        ]);
        return $this->success($payments);
    }

    public function show(Request $request, $id)
    {
        $payment = $this->paymentService->getPaymentById($id);
        if (!$payment) {
            return $this->errorNotFound('支付记录不存在');
        }
        $order = $this->orderService->getAdminOrderDetails($payment->order_id);
        return $this->success(['payment' => $payment, 'order' => $order]);
    }

    public function refund(Request $request, $id)
    {
        $data = $request->post();
        $validate = new PaymentValidate();
        $validate->failException(false);
        if (!$validate->scene('update')->check($data)) {
            throw new ValidationException($validate->getError());
        }
        $result = $this->paymentService->processRefund($id, $data);
        if (!$result) {
            return $this->error('退款处理失败');
        }
        return $this->success($result, '退款处理成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status');
        $remark = $request->post('remark', '');
        $result = $this->paymentService->updatePaymentStatusPublic($id, $status, $remark);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success(null, '更新成功');
    }

    public function stats(Request $request)
    {
        $appId = $this->getAppId($request);
        $stats = $this->paymentService->getPaymentStats($appId);
        return $this->success($stats);
    }

    public function export(Request $request)
    {
        $startDate = $request->get('start_date', '');
        $endDate = $request->get('end_date', '');
        $status = $request->get('status', '');
        $result = $this->paymentService->exportPayments($startDate, $endDate, $status);
        if (!$result) {
            return $this->error('导出失败');
        }
        return $this->success($result, '导出成功');
    }

    public function batchRefund(Request $request)
    {
        $ids = (array)$request->post('ids', []);
        if (empty($ids)) {
            return $this->error('缺少ids');
        }
        $ok = $this->paymentService->batchRefund($ids);
        return $ok ? $this->success(null, '批量退款成功') : $this->error('批量退款失败');
    }
}
