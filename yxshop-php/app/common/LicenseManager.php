<?php

declare(strict_types=1);

/**
 * YXShop 授权管理器
 *
 * 职责：
 *  - 读取 .env 中的 APP_EDITION 和 LICENSE_KEY
 *  - 验证授权码格式、域名绑定、有效期
 *  - 提供版本查询和功能检查接口
 *  - 验证结果 Redis 缓存（30 分钟 TTL）
 *  - 离线降级：验证服务器不可达时使用本地缓存
 *
 * @package app\common
 */

namespace app\common;

use support\Redis;
use support\Log;

class LicenseManager
{
    /**
     * 授权码签名密钥（用于校验码计算）
     * 注意：此密钥可公开，因为它不是安全边界——真正的安全边界是在线验证服务器
     */
    private const SECRET_KEY = 'yxshop_license_secret_2026';

    /**
     * Redis 缓存键前缀
     */
    private const CACHE_PREFIX = 'yxshop_license:';

    /**
     * Redis 缓存 TTL（秒）— 30 分钟
     */
    private const CACHE_TTL = 1800;

    /**
     * 离线降级 TTL（秒）— 30 天
     */
    private const OFFLINE_TTL = 2592000;

    /**
     * 版本权重
     */
    private const EDITION_WEIGHTS = [
        'open_source' => 0,
        'commercial'   => 1,
        'enterprise'   => 2,
        'saas'         => 3,
    ];

    /**
     * 版本标识码（授权码中使用的短标识）
     */
    private const EDITION_CODES = [
        'OPEN' => 'open_source',
        'COMM' => 'commercial',
        'ENT'  => 'enterprise',
        'SAAS' => 'saas',
    ];

    /**
     * 各版本授权有效天数
     */
    private const EDITION_EXPIRY_DAYS = [
        'open_source' => 99999,
        'commercial'   => 365,
        'enterprise'   => 365,
        'saas'         => 30,
    ];

    /**
     * 单例实例
     */
    private static ?LicenseManager $instance = null;

    /**
     * 当前版本
     */
    private string $edition;

    /**
     * 授权码
     */
    private string $licenseKey;

    /**
     * 验证结果缓存
     */
    private ?array $cachedResult = null;

    /**
     * 私有构造函数
     */
    private function __construct()
    {
        $this->edition = env('APP_EDITION', 'open_source');
        $this->licenseKey = env('LICENSE_KEY', '');
    }

    /**
     * 获取单例实例
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * 获取当前版本
     */
    public function getEdition(): string
    {
        return $this->edition;
    }

    /**
     * 获取当前版本的中文名称
     */
    public function getEditionName(): string
    {
        return match ($this->edition) {
            'open_source' => '开源版',
            'commercial'  => '商业版',
            'enterprise'  => '企业版',
            'saas'        => 'SaaS 版',
            default       => '未知版本',
        };
    }

    /**
     * 获取授权码
     */
    public function getLicenseKey(): string
    {
        return $this->licenseKey;
    }

    /**
     * 授权是否有效
     */
    public function isValid(): bool
    {
        // 开源版始终有效
        if ($this->edition === 'open_source') {
            return true;
        }

        // 试用模式
        if ($this->isTrialMode()) {
            return !$this->isTrialExpired();
        }

        // 验证授权码
        return $this->verifyWithCache();
    }

    /**
     * 检查是否有某个功能模块
     */
    public function hasFeature(string $feature): bool
    {
        $features = config('license.features', []);

        if (!isset($features[$feature])) {
            return false;
        }

        $minEdition = $features[$feature]['min_edition'];
        return $this->editionWeight($this->edition) >= $this->editionWeight($minEdition);
    }

    /**
     * 检查是否有某个模块（hasFeature 的别名，语义更清晰）
     */
    public function hasModule(string $module): bool
    {
        return $this->hasFeature($module);
    }

    /**
     * 检查当前版本是否 >= 指定版本
     */
    public function isAtLeast(string $edition): bool
    {
        return $this->editionWeight($this->edition) >= $this->editionWeight($edition);
    }

    /**
     * 是否试用模式
     */
    public function isTrialMode(): bool
    {
        return (bool) env('LICENSE_TRIAL_ENABLED', false);
    }

    /**
     * 试用是否已过期
     */
    public function isTrialExpired(): bool
    {
        $trialStart = Redis::get(self::CACHE_PREFIX . 'trial_start');
        if (!$trialStart) {
            // 试用未启动
            return true;
        }

        $trialDays = (int) config('license.trial.days', 14);
        $expiry = (int) $trialStart + ($trialDays * 86400);

        return time() > $expiry;
    }

    /**
     * 启动试用
     */
    public function startTrial(): bool
    {
        $key = self::CACHE_PREFIX . 'trial_start';
        Redis::set($key, (string) time());
        return true;
    }

    /**
     * 获取试用剩余天数
     */
    public function getTrialRemainingDays(): int
    {
        $trialStart = (int) Redis::get(self::CACHE_PREFIX . 'trial_start');
        if (!$trialStart) {
            return 0;
        }

        $trialDays = (int) config('license.trial.days', 14);
        $expiry = $trialStart + ($trialDays * 86400);
        $remaining = $expiry - time();

        return max(0, (int) ceil($remaining / 86400));
    }

    /**
     * 获取授权到期时间
     */
    public function getExpiry(): ?string
    {
        if ($this->edition === 'open_source') {
            return null;
        }

        if ($this->isTrialMode()) {
            $trialStart = (int) Redis::get(self::CACHE_PREFIX . 'trial_start');
            if (!$trialStart) {
                return null;
            }
            $trialDays = (int) config('license.trial.days', 14);
            return date('Y-m-d', $trialStart + ($trialDays * 86400));
        }

        // 从授权码解析到期时间
        $parts = explode('-', $this->licenseKey);
        if (count($parts) !== 4) {
            return null;
        }

        [, , $timestamp] = $parts;
        $licenseDate = \DateTime::createFromFormat('Ymd', $timestamp);
        if (!$licenseDate) {
            return null;
        }

        $expiryDays = self::EDITION_EXPIRY_DAYS[$this->edition] ?? 365;
        $expiry = (clone $licenseDate)->modify("+{$expiryDays} days");

        return $expiry->format('Y-m-d');
    }

    /**
     * 获取绑定域名
     */
    public function getDomain(): string
    {
        // 从授权码解析
        $parts = explode('-', $this->licenseKey);
        if (count($parts) === 4) {
            return $parts[1]; // domain_hash
        }
        return '';
    }

    /**
     * 验证授权码（带缓存）
     */
    private function verifyWithCache(): bool
    {
        if ($this->cachedResult !== null) {
            return $this->cachedResult['valid'];
        }

        // 尝试从 Redis 读取缓存
        $cacheKey = self::CACHE_PREFIX . 'verify_result';
        $cached = Redis::get($cacheKey);

        if ($cached) {
            $result = json_decode($cached, true);
            if (isset($result['valid'])) {
                $this->cachedResult = $result;
                return $result['valid'];
            }
        }

        // 本地验证（格式 + 域名 + 校验码 + 有效期）
        $localValid = $this->verifyLocal();

        // 尝试在线验证（非阻塞，失败时使用本地结果）
        $onlineValid = $this->verifyOnline();

        $valid = $onlineValid ?? $localValid;

        // 缓存结果
        $result = [
            'valid' => $valid,
            'verified_at' => time(),
            'source' => $onlineValid !== null ? 'online' : 'local',
        ];

        $ttl = $onlineValid !== null ? self::CACHE_TTL : self::OFFLINE_TTL;
        Redis::setex($cacheKey, $ttl, json_encode($result));
        $this->cachedResult = $result;

        return $valid;
    }

    /**
     * 本地验证授权码
     *
     * 授权码格式：EDITION-DOMAIN_HASH-TIMESTAMP-CHECKSUM
     * 示例：COMM-ABC123XY-20260301-A1B2C3
     */
    public function verifyLocal(): bool
    {
        $key = $this->licenseKey;

        if (empty($key)) {
            return false;
        }

        $parts = explode('-', $key);
        if (count($parts) !== 4) {
            return false;
        }

        [$editionCode, $domainHash, $timestamp, $checksum] = $parts;

        // 1. 验证版本标识
        $edition = self::EDITION_CODES[$editionCode] ?? null;
        if (!$edition || $edition !== $this->edition) {
            return false;
        }

        // 2. 验证校验码
        $expectedChecksum = substr(
            hash('sha256', $editionCode . $domainHash . $timestamp . self::SECRET_KEY),
            0, 6
        );
        if ($checksum !== $expectedChecksum) {
            return false;
        }

        // 3. 验证有效期
        $licenseDate = \DateTime::createFromFormat('Ymd', $timestamp);
        if (!$licenseDate) {
            return false;
        }

        $expiryDays = self::EDITION_EXPIRY_DAYS[$this->edition] ?? 365;
        $expiry = (clone $licenseDate)->modify("+{$expiryDays} days");

        if (new \DateTime() > $expiry) {
            return false;
        }

        return true;
    }

    /**
     * 在线验证授权码（非阻塞，失败时返回 null）
     */
    private function verifyOnline(): ?bool
    {
        if (empty($this->licenseKey)) {
            return null;
        }

        $server = config('license.verify_server', 'https://license.yxshop.com');
        $url = $server . '/api/license/verify';

        $postData = json_encode([
            'key' => $this->licenseKey,
            'domain' => $this->getDomain(),
        ]);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 3,  // 3 秒超时，不阻塞
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_SSL_VERIFYPEER => (bool) env('CURL_SSL_VERIFY', true),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error || $httpCode !== 200) {
            // 在线验证失败，使用本地结果
            Log::warning('License online verification failed, falling back to local', [
                'error' => $error,
                'http_code' => $httpCode,
            ]);
            return null;
        }

        $result = json_decode($response, true);
        return $result['valid'] ?? false;
    }

    /**
     * 根据路由路径获取所需的功能模块
     *
     * @param string $path 请求路径
     * @return string|null 所需模块名，null 表示开源版路由（无限制）
     */
    public function getRequiredModuleByRoute(string $path): ?string
    {
        $routeMap = config('license.route_module_map', []);

        foreach ($routeMap as $prefix => $module) {
            if (str_starts_with($path, $prefix)) {
                return $module;
            }
        }

        return null;
    }

    /**
     * 检查路由是否允许访问
     */
    public function isRouteAllowed(string $path): bool
    {
        $module = $this->getRequiredModuleByRoute($path);

        // 开源版路由，始终允许
        if ($module === null) {
            return true;
        }

        // 商业版/企业版路由，检查授权
        if (!$this->isValid()) {
            return false;
        }

        return $this->hasModule($module);
    }

    /**
     * 获取版本权重
     */
    private function editionWeight(string $edition): int
    {
        return self::EDITION_WEIGHTS[$edition] ?? 0;
    }

    /**
     * 生成授权码（管理工具用）
     *
     * @param string $edition  版本类型
     * @param string $domain   绑定域名
     * @param string $date     授权日期 YYYYMMDD
     * @return string 授权码
     */
    public static function generateLicenseKey(string $edition, string $domain, string $date): string
    {
        $editionCode = array_flip(self::EDITION_CODES)[$edition] ?? 'OPEN';
        $domainHash = substr(md5($domain), 0, 8);
        $checksum = substr(
            hash('sha256', $editionCode . $domainHash . $date . self::SECRET_KEY),
            0, 6
        );

        return implode('-', [$editionCode, $domainHash, $date, $checksum]);
    }

    /**
     * 清除缓存
     */
    public function clearCache(): void
    {
        Redis::del(self::CACHE_PREFIX . 'verify_result');
        $this->cachedResult = null;
    }
}
