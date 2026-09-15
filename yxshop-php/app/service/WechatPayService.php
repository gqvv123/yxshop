<?php

namespace app\service;

use app\repository\PaymentRepository;
use app\repository\SettingRepository;
use app\model\Payment;
use app\model\Order;
use support\Db;
use support\Log;
use Exception;

class WechatPayService extends BaseService
{
    protected SettingRepository $settingRepository;

    public function __construct()
    {
        parent::__construct(new PaymentRepository());
        $this->settingRepository = new SettingRepository();
    }

    public function verifyNotify(array $data, int $appId = 0): bool
    {
        try {
            $config = WechatPayConfigProvider::getConfig($appId);
            if (empty($config->apiKey)) {
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
            $signString .= 'key=' . $config->apiKey;

            if (strtoupper(md5($signString)) !== strtoupper($sign)) {
                return false;
            }

            if (($data['result_code'] ?? '') !== 'SUCCESS' && ($data['return_code'] ?? '') !== 'SUCCESS') {
                return false;
            }

            return true;
        } catch (Exception $e) {
            $this->logError('微信支付回调验证异常', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function verifyRefundNotify(array $data, int $appId = 0): bool
    {
        try {
            return $this->verifyNotify($data, $appId);
        } catch (Exception $e) {
            $this->logError('微信退款回调验证异常', ['error' => $e->getMessage()]);
            return false;
        }
    }

    public function createOrder(WechatPayConfig $config, string $openid, string $orderNo, int $amountFen, string $description): array
    {
        $params = [
            'appid' => $config->appId,
            'mch_id' => $config->mchId,
            'nonce_str' => md5(uniqid(mt_rand(), true)),
            'body' => mb_substr($description, 0, 128),
            'out_trade_no' => $orderNo,
            'total_fee' => $amountFen,
            'spbill_create_ip' => request()->getRealIp() ?? '127.0.0.1',
            'notify_url' => $config->notifyUrl,
            'trade_type' => 'JSAPI',
            'openid' => $openid,
        ];

        $params['sign'] = $this->makeSign($params, $config->apiKey);

        $xml = $this->arrayToXml($params);
        $response = $this->postXml('https://api.mch.weixin.qq.com/pay/unifiedorder', $xml);

        $result = $this->xmlToArray($response);

        if (($result['return_code'] ?? '') !== 'SUCCESS' || ($result['result_code'] ?? '') !== 'SUCCESS') {
            $errCode = $result['err_code'] ?? ($result['return_msg'] ?? 'UNKNOWN');
            $errDesc = $result['err_code_des'] ?? '';
            $this->logError('微信统一下单失败', ['order_no' => $orderNo, 'err_code' => $errCode, 'err_code_des' => $errDesc, 'mch_id' => $config->mchId]);
            throw new \app\exception\BusinessException('微信支付下单失败: ' . ($errDesc ?: $errCode));
        }

        $prepayId = $result['prepay_id'] ?? '';
        if (empty($prepayId)) {
            throw new \app\exception\BusinessException('微信支付下单失败: prepay_id为空');
        }

        $jsapiParams = [
            'appId' => $config->appId,
            'timeStamp' => (string) time(),
            'nonceStr' => md5(uniqid(mt_rand(), true)),
            'package' => 'prepay_id=' . $prepayId,
            'signType' => 'MD5',
        ];
        $jsapiParams['paySign'] = $this->makeSign($jsapiParams, $config->apiKey);

        return $jsapiParams;
    }

    public function processRefund(WechatPayConfig $config, string $orderNo, string $refundNo, int $totalFee, int $refundFee, string $refundReason = ''): array
    {
        if (!$config->hasCert()) {
            throw new \app\exception\BusinessException('请先配置微信支付证书');
        }

        $params = [
            'appid' => $config->appId,
            'mch_id' => $config->mchId,
            'nonce_str' => md5(uniqid(mt_rand(), true)),
            'out_trade_no' => $orderNo,
            'out_refund_no' => $refundNo,
            'total_fee' => $totalFee,
            'refund_fee' => $refundFee,
            'op_user_id' => $config->mchId,
        ];
        if (!empty($refundReason)) {
            $params['refund_desc'] = mb_substr($refundReason, 0, 80);
        }

        $params['sign'] = $this->makeSign($params, $config->apiKey);

        $xml = $this->arrayToXml($params);
        $response = $this->postXml('https://api.mch.weixin.qq.com/secapi/pay/refund', $xml, $config->certPath, $config->keyPath);

        $result = $this->xmlToArray($response);

        if (($result['return_code'] ?? '') !== 'SUCCESS' || ($result['result_code'] ?? '') !== 'SUCCESS') {
            $errCode = $result['err_code'] ?? ($result['return_msg'] ?? 'UNKNOWN');
            $errDesc = $result['err_code_des'] ?? '';
            $this->logError('微信退款失败', ['out_trade_no' => $orderNo, 'out_refund_no' => $refundNo, 'err_code' => $errCode, 'err_code_des' => $errDesc]);
            throw new \app\exception\BusinessException('微信退款失败: ' . ($errDesc ?: $errCode));
        }

        return [
            'refund_id' => $result['refund_id'] ?? '',
            'status' => 'processing',
        ];
    }

    public function testConnection(WechatPayConfig $config): array
    {
        if (!$config->isReady()) {
            return ['success' => false, 'message' => '请先完成微信支付必填配置'];
        }

        $params = [
            'appid' => $config->appId,
            'mch_id' => $config->mchId,
            'nonce_str' => md5(uniqid(mt_rand(), true)),
            'out_trade_no' => 'TEST_' . time(),
        ];
        $params['sign'] = $this->makeSign($params, $config->apiKey);

        $xml = $this->arrayToXml($params);
        try {
            $response = $this->postXml('https://api.mch.weixin.qq.com/pay/orderquery', $xml, '', '', 5);
        } catch (Exception $e) {
            return ['success' => false, 'message' => '网络不通，请检查服务器网络'];
        }

        $result = $this->xmlToArray($response);
        $returnCode = $result['return_code'] ?? '';

        if ($returnCode !== 'SUCCESS') {
            return ['success' => false, 'message' => '通信失败: ' . ($result['return_msg'] ?? '未知错误')];
        }

        $errCode = $result['err_code'] ?? '';
        if ($errCode === 'SIGNERROR') {
            return ['success' => false, 'message' => '配置有误：签名错误，请检查API密钥'];
        }
        if ($errCode === 'ORDERNOTEXIST') {
            return ['success' => true, 'message' => '连通成功'];
        }

        return ['success' => true, 'message' => '连通成功'];
    }

    private function makeSign(array $params, string $apiKey): string
    {
        ksort($params);
        $signString = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null && $key !== 'sign') {
                $signString .= "{$key}={$value}&";
            }
        }
        $signString .= 'key=' . $apiKey;
        return strtoupper(md5($signString));
    }

    private function arrayToXml(array $params): string
    {
        $xml = '<xml>';
        foreach ($params as $key => $value) {
            $xml .= "<{$key}><![CDATA[{$value}]]></{$key}>";
        }
        $xml .= '</xml>';
        return $xml;
    }

    private function xmlToArray(string $xml): array
    {
        if (PHP_MAJOR_VERSION < 8) {
            libxml_disable_entity_loader(true);
        }
        $data = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        return json_decode(json_encode($data), true);
    }

    private function postXml(string $url, string $xml, string $certPath = '', string $keyPath = '', int $timeout = 30): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);

        if (!empty($certPath) && !empty($keyPath)) {
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'PEM');
            curl_setopt($ch, CURLOPT_SSLKEY, $keyPath);
        }

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException('cURL error: ' . $error, $errno);
        }

        return $response;
    }

    public function createOrderV3(WechatPayConfig $config, string $openid, string $orderNo, int $amountFen, string $description): array
    {
        if (!$config->isV3Ready()) {
            throw new \app\exception\BusinessException('微信支付V3配置不完整，需配置V3密钥和证书序列号');
        }

        $body = [
            'appid' => $config->appId,
            'mchid' => $config->mchId,
            'description' => mb_substr($description, 0, 128),
            'out_trade_no' => $orderNo,
            'notify_url' => $config->notifyUrl,
            'amount' => [
                'total' => $amountFen,
                'currency' => 'CNY',
            ],
            'payer' => [
                'openid' => $openid,
            ],
        ];

        $jsonBody = json_encode($body, JSON_UNESCAPED_UNICODE);
        $timestamp = (string) time();
        $nonceStr = md5(uniqid(mt_rand(), true));

        $authorization = $this->buildV3Authorization($config, 'POST', '/v3/pay/transactions/jsapi', $timestamp, $nonceStr, $jsonBody);

        $response = $this->postJson('https://api.mch.weixin.qq.com/v3/pay/transactions/jsapi', $jsonBody, $authorization);

        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \app\exception\BusinessException('微信V3下单响应解析失败');
        }

        if (isset($result['code']) && $result['code'] !== 'SUCCESS') {
            $errCode = $result['code'] ?? 'UNKNOWN';
            $errMsg = $result['message'] ?? '';
            $this->logError('微信V3统一下单失败', ['order_no' => $orderNo, 'err_code' => $errCode, 'err_msg' => $errMsg, 'mch_id' => $config->mchId]);
            throw new \app\exception\BusinessException('微信V3支付下单失败: ' . $errMsg);
        }

        $prepayId = $result['prepay_id'] ?? '';
        if (empty($prepayId)) {
            throw new \app\exception\BusinessException('微信V3支付下单失败: prepay_id为空');
        }

        $jsapiParams = [
            'appId' => $config->appId,
            'timeStamp' => $timestamp,
            'nonceStr' => $nonceStr,
            'package' => 'prepay_id=' . $prepayId,
            'signType' => 'RSA',
        ];
        $jsapiParams['paySign'] = $this->signJsapiV3($config, $jsapiParams);

        return $jsapiParams;
    }

    public function verifyNotifyV3(array $headers, string $body): array
    {
        $timestamp = $headers['wechatpay-timestamp'] ?? '';
        $nonce = $headers['wechatpay-nonce'] ?? '';
        $signature = $headers['wechatpay-signature'] ?? '';
        $serialNo = $headers['wechatpay-serial'] ?? '';

        if (empty($timestamp) || empty($nonce) || empty($signature)) {
            $this->logError('微信V3回调缺少必要头部');
            return ['valid' => false];
        }

        $message = "{$timestamp}\n{$nonce}\n{$body}\n";

        $appId = $this->resolveAppIdBySerialNo($serialNo);

        $config = WechatPayConfigProvider::getConfig($appId);

        if (!$config->isV3Ready()) {
            $this->logError('微信V3回调验签失败: V3配置不完整', ['app_id' => $appId]);
            return ['valid' => false];
        }

        if (!$config->hasPlatformCert()) {
            $this->logError('微信V3回调验签失败: 未配置微信平台证书');
            return ['valid' => false];
        }

        $platformCertContent = file_get_contents($config->platformCertPath);
        $pubKeyId = openssl_pkey_get_public($platformCertContent);
        if (!$pubKeyId) {
            $this->logError('微信V3回调验签失败: 平台证书加载失败');
            return ['valid' => false];
        }

        $verified = (bool) openssl_verify($message, base64_decode($signature), $pubKeyId, OPENSSL_ALGO_SHA256);
        openssl_free_key($pubKeyId);

        if (!$verified) {
            $this->logError('微信V3回调验签失败', ['serial_no' => $serialNo]);
            return ['valid' => false];
        }

        $data = json_decode($body, true);
        if (!isset($data['resource'])) {
            return ['valid' => false];
        }

        $decrypted = $this->decryptV3Resource(
            $data['resource']['ciphertext'] ?? '',
            $data['resource']['nonce'] ?? '',
            $data['resource']['associated_data'] ?? '',
            $config->apiV3Key
        );

        if ($decrypted === false) {
            $this->logError('微信V3回调解密失败');
            return ['valid' => false];
        }

        return ['valid' => true, 'data' => json_decode($decrypted, true), 'app_id' => $appId];
    }

    private function resolveAppIdBySerialNo(string $serialNo): int
    {
        if (empty($serialNo)) {
            return 0;
        }

        try {
            $settings = $this->settingRepository->getByGroupAndKey('payment', 'wxpay_serial_no');

            foreach ($settings as $setting) {
                $appId = (int) ($setting->app_id ?? 0);
                $config = WechatPayConfigProvider::getConfig($appId);
                if ($config->serialNo === $serialNo && $config->isV3Ready()) {
                    return $appId;
                }
            }
        } catch (\Exception $e) {
        }

        return 0;
    }

    private function buildV3Authorization(WechatPayConfig $config, string $method, string $url, string $timestamp, string $nonceStr, string $body): string
    {
        $signMessage = "{$method}\n{$url}\n{$timestamp}\n{$nonceStr}\n{$body}\n";

        $signature = $this->signWithPrivateKey($config->keyPath, $signMessage);

        $signBase64 = base64_encode($signature);

        return 'WECHATPAY2-SHA256-RSA2048 mchid="' . $config->mchId . '",nonce_str="' . $nonceStr . '",timestamp="' . $timestamp . '",serial_no="' . $config->serialNo . '",signature="' . $signBase64 . '"';
    }

    private function signJsapiV3(WechatPayConfig $config, array $params): string
    {
        $signMessage = "{$params['appId']}\n{$params['timeStamp']}\n{$params['nonceStr']}\n{$params['package']}\n";

        $signature = $this->signWithPrivateKey($config->keyPath, $signMessage);

        return base64_encode($signature);
    }

    private function signWithPrivateKey(string $keyPath, string $message): string
    {
        $privateKey = file_get_contents($keyPath);
        $pkeyId = openssl_pkey_get_private($privateKey);
        if (!$pkeyId) {
            throw new \app\exception\BusinessException('商户私钥加载失败');
        }

        openssl_sign($message, $signature, $pkeyId, OPENSSL_ALGO_SHA256);
        openssl_free_key($pkeyId);

        return $signature;
    }

    private function decryptV3Resource(string $ciphertext, string $nonce, string $associatedData, string $apiV3Key): string|false
    {
        $ciphertextDecoded = base64_decode($ciphertext);
        if ($ciphertextDecoded === false) {
            return false;
        }

        $result = openssl_decrypt(
            $ciphertextDecoded,
            'aes-256-gcm',
            $apiV3Key,
            OPENSSL_RAW_DATA,
            $nonce,
            $associatedData
        );

        return $result;
    }

    private function postJson(string $url, string $jsonBody, string $authorization, int $timeout = 30): string
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: ' . $authorization,
        ]);

        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            throw new \RuntimeException('cURL error: ' . $error, $errno);
        }

        return $response;
    }
}
