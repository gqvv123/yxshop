<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\PaymentService;
use app\validate\PaymentValidate;
use app\exception\ValidationException;

class PaymentController extends BaseController
{
    /** @var PaymentService */
    protected $paymentService;

    public function __construct()
    {
        $this->paymentService = new PaymentService();
    }

    /**
     * 创建支付订单
     */
    public function create(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $data = $request->post();

        $validate = new PaymentValidate();
        if (!$validate->scene('api_create')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        if (empty($data['order_id'])) {
            return $this->error('订单ID不能为空');
        }
        if (empty($data['payment_method'])) {
            return $this->error('支付方式不能为空');
        }

        $result = $this->paymentService->createPayment(
            $data['order_id'],
            $data['payment_method'],
            $userId
        );
        return $this->success($result, '已创建支付');
    }

    /**
     * 获取支付状态
     */
    public function getStatus(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $paymentId = $request->get('payment_id');
        if (!$paymentId) {
            return $this->error('支付ID不能为空');
        }

        $payment = $this->paymentService->getPaymentStatus($paymentId);
        // 防止越权：只允许返回当前用户的记录
        if ($payment && (int) $payment->user_id !== (int) $userId) {
            return $this->error('无权查看该支付记录');
        }
        return $this->success($payment);
    }

    /**
     * 支付回调（备用，主要回调走 /pay/notify/{channel}）
     */
    public function callback(Request $request)
    {
        $data = $request->post();
        if (empty($data['payment_method'])) {
            return $this->error('payment_method 不能为空');
        }
        $result = $this->paymentService->handleCallback($data['payment_method'], $data);
        return $this->success(['result' => $result], '回调已处理');
    }

    /**
     * 申请退款
     */
    public function refund(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->post('order_id');
        $amount = (float) $request->post('refund_amount', 0);
        $reason = (string) $request->post('refund_reason', '');

        if (!$orderId) {
            return $this->error('订单ID不能为空');
        }
        if ($amount <= 0) {
            return $this->error('退款金额必须大于 0');
        }
        if ($reason === '') {
            return $this->error('退款原因不能为空');
        }

        $refund = $this->paymentService->refund($orderId, $amount, $reason);
        return $this->success($refund, '已提交退款');
    }
}
