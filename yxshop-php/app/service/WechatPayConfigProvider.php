<?php

namespace app\service;

class WechatPayConfig
{
    public $enable;
    public $appId;
    public $mchId;
    public $apiKey;
    public $notifyUrl;
    public $certPath;
    public $keyPath;
    public $refundNotifyUrl;
    public $apiV3Key;
    public $serialNo;
    public $platformCertPath;

    public function __construct(array $data = [])
    {
        $this->enable = (bool) ($data['enable'] ?? false);
        $this->appId = (string) ($data['appId'] ?? '');
        $this->mchId = (string) ($data['mchId'] ?? '');
        $this->apiKey = (string) ($data['apiKey'] ?? '');
        $this->notifyUrl = (string) ($data['notifyUrl'] ?? '');
        $this->certPath = (string) ($data['certPath'] ?? '');
        $this->keyPath = (string) ($data['keyPath'] ?? '');
        $this->refundNotifyUrl = (string) ($data['refundNotifyUrl'] ?? '');
        $this->apiV3Key = (string) ($data['apiV3Key'] ?? '');
        $this->serialNo = (string) ($data['serialNo'] ?? '');
        $this->platformCertPath = (string) ($data['platformCertPath'] ?? '');
    }

    public function hasCert(): bool
    {
        return !empty($this->certPath) && !empty($this->keyPath)
            && file_exists($this->certPath) && file_exists($this->keyPath);
    }

    public function isReady(): bool
    {
        return $this->enable
            && !empty($this->appId)
            && !empty($this->mchId)
            && !empty($this->apiKey)
            && !empty($this->notifyUrl);
    }

    public function isV3Ready(): bool
    {
        return $this->isReady()
            && !empty($this->apiV3Key)
            && $this->hasCert()
            && !empty($this->serialNo);
    }

    public function hasPlatformCert(): bool
    {
        return !empty($this->platformCertPath) && file_exists($this->platformCertPath);
    }
}

class WechatPayConfigProvider
{
    private static $cache = [];
    private static $cacheTtl = 60;

    public static function getConfig(int $appId = 0): WechatPayConfig
    {
        $now = time();
        $cacheKey = 'wxpay_' . $appId;

        if (isset(self::$cache[$cacheKey])) {
            $cached = self::$cache[$cacheKey];
            if ($now - $cached['ts'] < self::$cacheTtl) {
                return $cached['config'];
            }
        }

        $settingService = new SettingService();

        $config = new WechatPayConfig([
            'enable'          => self::envOrDb('WECHAT_MCH_ID', 'wxpay_enable', $appId, 0, fn($v) => !empty($v) && $v !== 'your_wechat_mch_id' ? 1 : 0),
            'appId'           => self::envOrDb('WECHAT_APP_ID', 'wxpay_appid', $appId, ''),
            'mchId'           => self::envOrDb('WECHAT_MCH_ID', 'wxpay_mchid', $appId, ''),
            'apiKey'          => self::envOrDb('WECHAT_KEY', 'wxpay_key', $appId, ''),
            'notifyUrl'       => self::envOrDb('WECHAT_NOTIFY_URL', 'wxpay_notify', $appId, ''),
            'certPath'        => self::envOrDb('WECHAT_CERT_PATH', 'wxpay_cert_path', $appId, ''),
            'keyPath'         => self::envOrDb('WECHAT_KEY_PATH', 'wxpay_key_path', $appId, ''),
            'refundNotifyUrl' => self::envOrDb('WECHAT_REFUND_NOTIFY_URL', 'wxpay_refund_notify', $appId, ''),
            'apiV3Key'        => self::envOrDb('WECHAT_V3_KEY', 'wxpay_v3_key', $appId, ''),
            'serialNo'        => self::envOrDb('WECHAT_SERIAL_NO', 'wxpay_serial_no', $appId, ''),
            'platformCertPath' => $settingService->getRawSetting('wxpay_platform_cert_path', $appId, ''),
        ]);

        self::$cache[$cacheKey] = [
            'config' => $config,
            'ts' => $now,
        ];

        return $config;
    }

    public static function clearCache(int $appId = 0): void
    {
        $cacheKey = 'wxpay_' . $appId;
        unset(self::$cache[$cacheKey]);
    }

    /**
     * 优先从 .env 读取，回退到数据库设置
     * 如果 $transform 回调存在，则对 env 值做转换（如自动推断 enable）
     */
    private static function envOrDb(string $envKey, string $dbKey, int $appId, $default = '', ?\Closure $transform = null)
    {
        $envVal = getenv($envKey);
        if ($envVal !== false && $envVal !== '' && !str_starts_with($envVal, 'your_') && $envVal !== '/path/to/') {
            return $transform ? $transform($envVal) : $envVal;
        }
        $settingService = new SettingService();
        return $settingService->getRawSetting($dbKey, $appId, $default);
    }
}