<?php

namespace app\service;

use support\Log;

class WechatApiClient
{
    const OAUTH_URL = 'https://open.weixin.qq.com/connect/qrconnect';
    const TOKEN_URL = 'https://api.weixin.qq.com/sns/oauth2/access_token';
    const USERINFO_URL = 'https://api.weixin.qq.com/sns/userinfo';

    /**
     * Mock 模式开关（仅用于本地开发调试，生产环境必须关闭）
     * @internal debug-only
     */
    private $mockMode;

    public function __construct()
    {
        $this->mockMode = env('WECHAT_MOCK_MODE', false);
    }

    public function isMockMode(): bool
    {
        return (bool) $this->mockMode;
    }

    public function buildOAuthUrl(string $appId, string $redirectUri, string $state): string
    {
        $params = http_build_query([
            'appid' => $appId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'snsapi_login',
            'state' => $state,
        ]);
        return self::OAUTH_URL . '?' . $params . '#wechat_redirect';
    }

    public function getAccessTokenByCode(string $code, string $appId, string $appSecret): array
    {
        if ($this->mockMode) {
            if (!env('APP_DEBUG', false)) {
                throw new \RuntimeException('Mock mode is only available in debug environment');
            }
            return [
                'access_token' => 'mock_access_token_' . $code,
                'openid' => 'mock_openid_' . md5($code),
                'unionid' => 'mock_unionid_' . md5($code),
                'expires_in' => 7200,
            ];
        }

        return $this->retry(function () use ($code, $appId, $appSecret) {
            $url = self::TOKEN_URL . '?' . http_build_query([
                'appid' => $appId,
                'secret' => $appSecret,
                'code' => $code,
                'grant_type' => 'authorization_code',
            ]);

            $result = $this->httpRequest($url);

            if (isset($result['errcode']) && $result['errcode'] !== 0) {
                throw new \RuntimeException("微信token接口错误: {$result['errmsg']} (errcode={$result['errcode']})");
            }

            return $result;
        });
    }

    public function getUserInfo(string $accessToken, string $openid): array
    {
        if ($this->mockMode) {
            if (!env('APP_DEBUG', false)) {
                throw new \RuntimeException('Mock mode is only available in debug environment');
            }
            return [
                'nickname' => '测试用户',
                'headimgurl' => '',
                'openid' => $openid,
                'unionid' => 'mock_unionid',
            ];
        }

        return $this->retry(function () use ($accessToken, $openid) {
            $url = self::USERINFO_URL . '?' . http_build_query([
                'access_token' => $accessToken,
                'openid' => $openid,
                'lang' => 'zh_CN',
            ]);

            $result = $this->httpRequest($url);

            if (isset($result['errcode']) && $result['errcode'] !== 0) {
                throw new \RuntimeException("微信userinfo接口错误: {$result['errmsg']} (errcode={$result['errcode']})");
            }

            return $result;
        });
    }

    private function httpRequest(string $url): array
    {
        Log::info('WechatApiClient request', ['url' => $url]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $sslVerify = (bool) env('CURL_SSL_VERIFY', false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $sslVerify);
        if ($sslVerify && env('CURL_CA_BUNDLE', '')) {
            curl_setopt($ch, CURLOPT_CAINFO, env('CURL_CA_BUNDLE'));
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            Log::error('WechatApiClient curl error', ['url' => $url, 'error' => $error]);
            throw new \RuntimeException("微信API请求失败: {$error}");
        }

        $result = json_decode($response, true);
        if (!$result) {
            Log::error('WechatApiClient json error', ['url' => $url, 'response' => $response]);
            throw new \RuntimeException("微信API响应解析失败");
        }

        Log::info('WechatApiClient response', ['url' => $url, 'result_keys' => array_keys($result)]);

        return $result;
    }

    private function retry(callable $fn, int $maxRetries = 2): mixed
    {
        $lastException = null;
        for ($i = 0; $i <= $maxRetries; $i++) {
            try {
                return $fn();
            } catch (\Throwable $e) {
                $lastException = $e;
                if ($i < $maxRetries) {
                    sleep(1);
                }
            }
        }
        throw $lastException;
    }
}