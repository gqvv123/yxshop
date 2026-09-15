<?php

namespace app\controller;

use app\service\WechatPayService;
use app\service\PaymentService;
use support\Request;
use support\Response;
use support\Log;

class WechatNotifyController extends BaseController
{
    private $wechatPayService;
    private $paymentService;

    public function __construct()
    {
        $this->wechatPayService = new WechatPayService();
        $this->paymentService = new PaymentService();
    }

    public function payNotify(Request $request): Response
    {
        try {
            $contentType = $request->header('content-type', '');

            if (stripos($contentType, 'application/json') !== false) {
                return $this->payNotifyV3($request);
            }

            $data = $request->all();

            Log::info('微信支付回调V2', [
                'ip' => $request->getRealIp(),
            ]);

            $result = $this->wechatPayService->verifyNotify($data);

            if ($result) {
                $this->paymentService->handleCallback('wechat', $data);

                return response('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>')
                    ->header('Content-Type', 'application/xml');
            }

            Log::error('微信支付回调验证失败');

            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[验证失败]]></return_msg></xml>')
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            Log::error('微信支付回调异常', [
                'error' => $e->getMessage(),
            ]);

            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[处理异常]]></return_msg></xml>')
                ->header('Content-Type', 'application/xml');
        }
    }

    public function payNotifyV3(Request $request): Response
    {
        try {
            $body = (string) $request->rawBody();

            $headers = [
                'wechatpay-timestamp' => $request->header('wechatpay-timestamp', ''),
                'wechatpay-nonce' => $request->header('wechatpay-nonce', ''),
                'wechatpay-signature' => $request->header('wechatpay-signature', ''),
                'wechatpay-serial' => $request->header('wechatpay-serial', ''),
            ];

            Log::info('微信支付回调V3', [
                'ip' => $request->getRealIp(),
                'serial' => $headers['wechatpay-serial'],
            ]);

            $result = $this->wechatPayService->verifyNotifyV3($headers, $body);

            if (!$result['valid']) {
                Log::error('微信V3支付回调验签失败');
                return new Response('', 401, ['Content-Type' => 'application/json']);
            }

            $callbackData = $result['data'];
            if (($callbackData['trade_state'] ?? '') === 'SUCCESS') {
                $this->paymentService->handleCallback('wechat', [
                    'out_trade_no' => $callbackData['out_trade_no'] ?? '',
                    'transaction_id' => $callbackData['transaction_id'] ?? '',
                    'result_code' => 'SUCCESS',
                    'return_code' => 'SUCCESS',
                ]);
            }

            return new Response('', 204);
        } catch (\Exception $e) {
            Log::error('微信V3支付回调异常', [
                'error' => $e->getMessage(),
            ]);

            return new Response('', 500, ['Content-Type' => 'application/json']);
        }
    }

    public function refundNotify(Request $request): Response
    {
        try {
            $data = $request->all();

            Log::info('微信退款回调', [
                'ip' => $request->getRealIp(),
            ]);

            $result = $this->wechatPayService->verifyRefundNotify($data);

            if ($result) {
                return response('<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>')
                    ->header('Content-Type', 'application/xml');
            }

            Log::error('微信退款回调验证失败');

            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[验证失败]]></return_msg></xml>')
                ->header('Content-Type', 'application/xml');
        } catch (\Exception $e) {
            Log::error('微信退款回调异常', [
                'error' => $e->getMessage(),
            ]);

            return response('<xml><return_code><![CDATA[FAIL]]></return_code><return_msg><![CDATA[处理异常]]></return_msg></xml>')
                ->header('Content-Type', 'application/xml');
        }
    }
}
