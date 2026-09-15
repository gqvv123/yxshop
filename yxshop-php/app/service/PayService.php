<?php

namespace app\service;

use app\repository\PaymentRepository;
use app\repository\OrderRepository;
use app\model\Payment;
use app\model\Order;
use support\Db;
use support\Log;
use app\exception\BusinessException;
use Exception;

class PayService extends BaseService
{
    protected $orderRepository;

    public function __construct()
    {
        parent::__construct(new PaymentRepository());
        $this->orderRepository = new OrderRepository();
    }

    public function handleNotify(string $paymentMethod, array $data): array
    {
        try {
            $this->logInfo('处理支付回调', [
                'payment_method' => $paymentMethod,
                'out_trade_no' => $data['out_trade_no'] ?? '',
            ]);

            switch ($paymentMethod) {
                case 'wechat':
                    $verified = $this->verifyWechatSignature($data);
                    break;
                case 'alipay':
                    $verified = $this->verifyAlipaySignature($data);
                    break;
                case 'unionpay':
                    $verified = $this->verifyUnionpaySignature($data);
                    break;
                default:
                    throw new BusinessException('不支持的支付方式: ' . $paymentMethod);
            }

            if (!$verified) {
                $this->logError('支付回调签名验证失败', [
                    'payment_method' => $paymentMethod,
                    'data' => $data,
                ]);
                return ['success' => false, 'message' => '签名验证失败'];
            }

            $outTradeNo = $data['out_trade_no'] ?? '';
            $transactionId = $data['transaction_id'] ?? '';

            if (empty($outTradeNo)) {
                return ['success' => false, 'message' => '缺少订单号'];
            }

            return $this->transaction(function () use ($outTradeNo, $transactionId, $paymentMethod) {
                $payment = $this->repository->findByOrderNoForUpdate((string) $outTradeNo);
                if (!$payment || (int) $payment->status !== Payment::STATUS_PENDING) {
                    $this->logError('支付记录不存在或已处理', ['order_no' => $outTradeNo]);
                    return ['success' => false, 'message' => '支付记录不存在或已处理'];
                }

                $payment->status = Payment::STATUS_SUCCESS;
                $payment->transaction_id = $transactionId;
                $payment->paid_at = time();
                $payment->save();

                $order = $this->orderRepository->findByOrderNoForUpdate((string) $outTradeNo);
                if (!$order) {
                    throw new Exception('订单不存在');
                }
                $order->updatePayStatus(Order::PAY_STATUS_PAID, $transactionId);

                $this->logInfo('支付回调处理成功', ['order_no' => $outTradeNo]);
                return ['success' => true, 'message' => 'ok'];
            });
        } catch (Exception $e) {
            $this->logError('处理支付回调异常', [
                'payment_method' => $paymentMethod,
                'error' => $e->getMessage(),
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function verifyWechatSignature(array $data): bool
    {
        $config = config('payment.wechat', []);
        if (empty($config['api_key'])) {
            // 安全策略：密钥未配置时直接拒绝，禁止 fail-open
            $this->logError('微信支付API密钥未配置，拒绝处理回调', ['out_trade_no' => $data['out_trade_no'] ?? '']);
            return false;
        }

        $sign = $data['sign'] ?? '';
        if (empty($sign)) {
            return false;
        }

        $signData = $data;
        unset($signData['sign'], $signData['sign_type']);
        ksort($signData);

        $signString = '';
        foreach ($signData as $key => $value) {
            if ($value !== '' && $value !== null) {
                $signString .= "{$key}={$value}&";
            }
        }
        $signString .= 'key=' . $config['api_key'];

        return strtoupper(md5($signString)) === strtoupper($sign);
    }

    private function verifyAlipaySignature(array $data): bool
    {
        $config = config('payment.alipay', []);
        if (empty($config['alipay_public_key'])) {
            // 安全策略：公钥未配置时直接拒绝
            $this->logError('支付宝公钥未配置，拒绝处理回调', ['out_trade_no' => $data['out_trade_no'] ?? '']);
            return false;
        }

        $sign = $data['sign'] ?? '';
        $signType = $data['sign_type'] ?? 'RSA2';
        if (empty($sign)) {
            return false;
        }

        $signData = $data;
        unset($signData['sign'], $signData['sign_type']);
        ksort($signData);

        $signString = '';
        foreach ($signData as $key => $value) {
            if ($value !== '' && $value !== null) {
                if ($signString !== '') {
                    $signString .= '&';
                }
                $signString .= "{$key}={$value}";
            }
        }

        $publicKey = "-----BEGIN PUBLIC KEY-----\n" .
            wordwrap($config['alipay_public_key'], 64, "\n", true) .
            "\n-----END PUBLIC KEY-----";

        $algorithm = $signType === 'RSA2' ? OPENSSL_ALGO_SHA256 : OPENSSL_ALGO_SHA1;
        $result = openssl_verify($signString, base64_decode($sign), $publicKey, $algorithm);

        return $result === 1;
    }

    private function verifyUnionpaySignature(array $data): bool
    {
        $config = config('payment.unionpay', []);
        if (empty($config['verify_cert'])) {
            // 安全策略：证书未配置时直接拒绝
            $this->logError('银联验签证书未配置，拒绝处理回调', ['out_trade_no' => $data['out_trade_no'] ?? '']);
            return false;
        }

        $signature = $data['signature'] ?? '';
        $outTradeNo = $data['out_trade_no'] ?? '';
        if (empty($signature) || empty($outTradeNo)) {
            return false;
        }

        // 银联验签：使用配置的公钥证书校验签名串
        // 待对接银联 SDK 完成正式验签前，禁止上线
        $this->logError('银联验签 SDK 尚未接入，拒绝处理回调以确保安全', ['out_trade_no' => $outTradeNo]);
        return false;
    }
}
