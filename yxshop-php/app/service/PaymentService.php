<?php

namespace app\service;

use app\repository\PaymentRepository;
use app\repository\OrderRepository;
use app\repository\UserRepository;
use app\model\Order;
use app\model\Payment;
use app\exception\BusinessException;
use Exception;

/**
 * 支付服务类
 *
 * @property PaymentRepository $repository
 */
class PaymentService extends BaseService
{
    protected $orderService;
    protected $orderRepository;
    protected $userRepository;

    public function __construct(PaymentRepository $repository = null)
    {
        $repository = $repository ?? new PaymentRepository();
        parent::__construct($repository);
        $this->orderService = new OrderService();
        $this->orderRepository = new OrderRepository();
        $this->userRepository = new UserRepository();
    }

    public function batchRefund(array $ids)
    {
        return $this->repository->updateWhere(['id' => $ids], ['payment_status' => 4]);
    }

    /**
     * 创建支付订单
     */
    public function createPayment($orderId, $paymentMethod, $userId)
    {
        try {
            $this->logInfo('创建支付订单开始', [
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'user_id' => $userId
            ]);

            $order = $this->orderService->findOrFail($orderId);
            
            if ($order->user_id != $userId) {
                throw new BusinessException('订单不属于当前用户');
            }

            if (!$order->canPay()) {
                throw new BusinessException('订单不能支付');
            }

            // 创建支付记录
            $payment = $this->repository->create([
                'order_id' => $orderId,
                'order_no' => $order->order_no,
                'user_id' => $userId,
                'payment_method' => $paymentMethod,
                'payment_amount' => $order->pay_price,
                'payment_status' => Payment::STATUS_PENDING,
                'app_id' => $order->app_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            // 根据支付方式处理
            switch ($paymentMethod) {
                case 'wechat':
                    $result = $this->createWechatPayment($payment);
                    break;
                case 'alipay':
                    $result = $this->createAlipayPayment($payment);
                    break;
                default:
                    throw new BusinessException('不支持的支付方式');
            }

            $this->logInfo('创建支付订单成功', ['payment_id' => $payment->id]);
            return $result;

        } catch (Exception $e) {
            $this->logError('创建支付订单失败', [
                'order_id' => $orderId,
                'payment_method' => $paymentMethod,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 支付回调处理
     */
    public function handleCallback($paymentMethod, $data)
    {
        try {
            $this->logInfo('处理支付回调开始', [
                'payment_method' => $paymentMethod,
                'data' => $data
            ]);

            switch ($paymentMethod) {
                case 'wechat':
                    return $this->handleWechatCallback($data);
                case 'alipay':
                    return $this->handleAlipayCallback($data);
                default:
                    throw new BusinessException('不支持的支付方式');
            }

        } catch (Exception $e) {
            $this->logError('处理支付回调失败', [
                'payment_method' => $paymentMethod,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 查询支付状态
     */
    public function getPaymentStatus($paymentId)
    {
        try {
            $payment = $this->repository->findOrFail($paymentId);
            return $payment;

        } catch (Exception $e) {
            $this->logError('查询支付状态失败', [
                'payment_id' => $paymentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 申请退款
     */
    public function refund($orderId, $amount, $reason = '')
    {
        try {
            $this->logInfo('申请退款开始', [
                'order_id' => $orderId,
                'amount' => $amount,
                'reason' => $reason
            ]);

            $order = $this->orderService->findOrFail($orderId);
            $payment = $this->repository->findSuccessByOrderId((int) $orderId);
            if (!$payment) {
                throw new BusinessException('未找到对应的成功支付记录');
            }

            if ($amount > $payment->amount) {
                throw new BusinessException('退款金额不能超过支付金额');
            }

            // 创建退款记录
            $refund = $this->repository->create([
                'order_id' => $orderId,
                'order_no' => $order->order_no,
                'user_id' => $order->user_id,
                'payment_method' => $payment->payment_method,
                'payment_amount' => -$amount, // 负数表示退款
                'payment_status' => Payment::STATUS_PENDING,
                'refund_reason' => $reason,
                'app_id' => $order->app_id,
                'created_at' => time(),
                'updated_at' => time(),
            ]);

            // 根据支付方式处理退款
            switch ($payment->payment_method) {
                case 'wechat':
                    $result = $this->processWechatRefund($refund);
                    break;
                case 'alipay':
                    $result = $this->processAlipayRefund($refund);
                    break;
                default:
                    throw new BusinessException('不支持的支付方式');
            }

            $this->logInfo('申请退款成功', ['refund_id' => $refund->id]);
            return $result;

        } catch (Exception $e) {
            $this->logError('申请退款失败', [
                'order_id' => $orderId,
                'amount' => $amount,
                'reason' => $reason,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建微信支付
     */
    private function createWechatPayment($payment)
    {
        $appId = (int) ($payment->app_id ?? 0);
        $config = WechatPayConfigProvider::getConfig($appId);

        if (!$config->enable) {
            throw new BusinessException('微信支付未启用');
        }

        $order = $this->orderRepository->find($payment->order_id);
        if (!$order) {
            throw new BusinessException('订单不存在');
        }

        $user = $this->userRepository->find($order->user_id);
        if (!$user || empty($user->wechat_openid)) {
            throw new BusinessException('请先完成微信登录后再支付');
        }

        $amountFen = (int) round($payment->amount * 100);
        $orderNo = $payment->order_no;
        $description = '订单支付 ' . $orderNo;

        $wechatPayService = new WechatPayService();

        if ($config->isV3Ready()) {
            try {
                $jsapiParams = $wechatPayService->createOrderV3($config, $user->wechat_openid, $orderNo, $amountFen, $description);
                return array_merge(['payment_id' => $payment->id], $jsapiParams);
            } catch (\Exception $e) {
                \support\Log::info('微信V3下单失败，降级到V2', ['order_no' => $orderNo, 'error' => $e->getMessage()]);
            }
        }

        $jsapiParams = $wechatPayService->createOrder($config, $user->wechat_openid, $orderNo, $amountFen, $description);

        return array_merge(['payment_id' => $payment->id], $jsapiParams);
    }

    /**
     * 创建支付宝支付
     */
    private function createAlipayPayment($payment)
    {
        // 这里集成支付宝SDK
        // 简化处理，返回模拟数据
        return [
            'payment_id' => $payment->id,
            'payment_url' => 'https://openapi.alipay.com/gateway.do?app_id=' . $payment->id,
        ];
    }

    /**
     * 处理微信支付回调
     */
    private function handleWechatCallback($data)
    {
        // 验证微信支付回调签名
        // 更新支付状态
        $this->updatePaymentStatus($data['out_trade_no'], Payment::STATUS_SUCCESS);
        return 'SUCCESS';
    }

    private function handleAlipayCallback($data)
    {
        $this->updatePaymentStatus($data['out_trade_no'], Payment::STATUS_SUCCESS);
        return 'success';
    }

    private function updatePaymentStatus($orderNo, $status)
    {
        // 事务包裹 payment + order 双表写，避免支付成功但订单状态更新失败导致不一致
        $this->transaction(function () use ($orderNo, $status) {
            $payment = $this->repository->findByOrderNoForUpdate((string) $orderNo);
            if (!$payment) {
                throw new BusinessException('支付记录不存在');
            }

            // 幂等：已成功的支付不重复处理
            if ((int) $payment->payment_status === Payment::STATUS_SUCCESS) {
                return;
            }

            $payment->payment_status = $status;
            $payment->paid_at = time();
            $payment->save();

            $order = $this->orderRepository->findByOrderNoForUpdate((string) $orderNo);
            if (!$order) {
                throw new BusinessException('订单不存在');
            }
            $order->updatePayStatus(\app\model\Order::PAY_STATUS_PAID, $payment->transaction_id ?? '');
        });
    }

    /**
     * 处理微信退款
     */
    private function processWechatRefund($refund)
    {
        $appId = (int) ($refund->app_id ?? 0);
        $config = WechatPayConfigProvider::getConfig($appId);

        if (!$config->hasCert()) {
            throw new BusinessException('请先配置微信支付证书');
        }

        $originalPayment = $this->repository->findSuccessByOrderId($refund->order_id);
        if (!$originalPayment) {
            throw new BusinessException('原支付记录不存在');
        }

        $totalFee = (int) round($originalPayment->amount * 100);
        $refundFee = (int) round($refund->amount * 100);

        $wechatPayService = new WechatPayService();
        $result = $wechatPayService->processRefund(
            $config,
            $originalPayment->order_no,
            $refund->refund_no ?? ('RF' . $refund->id),
            $totalFee,
            $refundFee,
            $refund->refund_reason ?? ''
        );

        $refund->payment_status = Payment::STATUS_PENDING;
        $refund->save();

        return $result;
    }

    /**
     * 处理支付宝退款
     */
    private function processAlipayRefund($refund)
    {
        return ['refund_id' => $refund->id, 'status' => 'processing'];
    }

    public function getPaymentList($page, $limit, array $filters = [])
    {
        return $this->repository->getPaginatedList($filters, (int) $page, (int) $limit);
    }

    public function getPaymentById($id)
    {
        return $this->repository->findOrFail($id);
    }

    public function processRefund($id, array $data)
    {
        $payment = $this->repository->findOrFail($id);
        $payment->payment_status = Payment::STATUS_REFUNDING;
        $payment->refund_reason = $data['reason'] ?? '';
        $payment->save();
        return $payment;
    }

    public function updatePaymentStatusPublic($id, $status, $remark = '')
    {
        $payment = $this->repository->findOrFail($id);
        $payment->payment_status = $status;
        if ($remark) {
            $payment->remark = $remark;
        }
        $payment->save();
        return $payment;
    }

    public function getPaymentStats($appId = 0)
    {
        return $this->repository->getAdminStats((int) $appId);
    }

    public function exportPayments($startDate, $endDate, $status = '')
    {
        return $this->repository->exportByDateRange($startDate, $endDate, $status);
    }

    /**
     * 支付方式分布统计（成功支付）
     */
    public function getPaymentMethodStats(int $appId): array
    {
        return $this->repository->getPaymentMethodStats(null, null, $appId);
    }
}
