<?php

namespace app\admin\controller;

use support\Request;
use app\common\LicenseManager;
use app\exception\BusinessException;

/**
 * 授权管理控制器
 *
 * 提供後台授权状态查询、授权碼激活、试用模式管理等功能。
 *
 * 路由前綴：/admin/api/license
 */
class LicenseController extends BaseController
{
    private LicenseManager $licenseManager;

    public function __construct()
    {
        $this->licenseManager = LicenseManager::getInstance();
    }

    /**
     * 获取當前授权状态
     *
     * GET /admin/api/license/status
     */
    public function status(Request $request)
    {
        $lm = $this->licenseManager;

        $edition = $lm->getEdition();
        $editionName = $lm->getEditionName();
        $isValid = $lm->isValid();
        $isTrial = $lm->isTrialMode();
        $isTrialExpired = $isTrial ? $lm->isTrialExpired() : false;
        $trialRemaining = $isTrial ? $lm->getTrialRemainingDays() : 0;
        $expiry = $lm->getExpiry();
        $domain = $lm->getDomain();

        // 获取功能列表及启用状态
        $features = config('license.features', []);
        $featureList = [];
        foreach ($features as $key => $feature) {
            $featureList[] = [
                'key'         => $key,
                'name'        => $feature['name'],
                'min_edition' => $feature['min_edition'],
                'enabled'     => $lm->hasFeature($key),
            ];
        }

        // 版本信息
        $editions = [
            'open_source' => ['name' => '开源版', 'price' => '免費', 'features' => '基礎电商功能'],
            'commercial'  => ['name' => '商业版', 'price' => '¥2,980/年', 'features' => '分銷、秒杀、会员卡、社区养老等'],
            'enterprise'  => ['name' => '企业版', 'price' => '¥9,800/年', 'features' => '多店铺、供应鏈、审计追踪等'],
            'saas'        => ['name' => 'SaaS 版', 'price' => '按需付費', 'features' => '託管服务、自动運維'],
        ];

        return $this->success([
            'edition'           => $edition,
            'edition_name'      => $editionName,
            'is_valid'          => $isValid,
            'is_trial'          => $isTrial,
            'is_trial_expired'  => $isTrialExpired,
            'trial_remaining'   => $trialRemaining,
            'expiry'            => $expiry,
            'domain'            => $domain,
            'license_key_masked'=> $this->maskLicenseKey($lm->getLicenseKey()),
            'features'          => $featureList,
            'editions'          => $editions,
        ]);
    }

    /**
     * 激活授权碼
     *
     * POST /admin/api/license/activate
     *
     * 请求參數：
     * - license_key: 授权碼
     * - edition: 版本类型（commercial / enterprise）
     */
    public function activate(Request $request)
    {
        $licenseKey = trim($request->post('license_key', ''));
        $edition = trim($request->post('edition', ''));

        if (empty($licenseKey)) {
            throw new BusinessException('授权碼不能為空');
        }

        if (empty($edition) || !in_array($edition, ['commercial', 'enterprise'])) {
            throw new BusinessException('版本类型无效，必須為 commercial 或 enterprise');
        }

        // 验证授权碼格式
        $parts = explode('-', $licenseKey);
        if (count($parts) !== 4) {
            throw new BusinessException('授权碼格式错误，應為 EDITION-DOMAIN_HASH-DATE-CHECKSUM');
        }

        [$editionCode, $domainHash, $timestamp, $checksum] = $parts;

        // 验证版本標識
        $editionCodes = ['COMM' => 'commercial', 'ENT' => 'enterprise', 'SAAS' => 'saas'];
        $decodedEdition = $editionCodes[$editionCode] ?? null;
        if (!$decodedEdition || $decodedEdition !== $edition) {
            throw new BusinessException("授权碼版本標識與请求版本不匹配（碼中為 {$editionCode}，期望 {$edition}）");
        }

        // 验证校验码
        $expectedChecksum = substr(
            hash('sha256', $editionCode . $domainHash . $timestamp . 'yxshop_license_secret_2026'),
            0, 6
        );
        if ($checksum !== $expectedChecksum) {
            throw new BusinessException('授权碼校验码验证失败，請检查授权碼是否完整');
        }

        // 验证日期格式
        $dateObj = \DateTime::createFromFormat('Ymd', $timestamp);
        if (!$dateObj) {
            throw new BusinessException('授权碼日期格式无效');
        }

        // 验证有效期
        $expiryDays = $edition === 'commercial' ? 365 : 365;
        $expiry = (clone $dateObj)->modify("+{$expiryDays} days");
        if (new \DateTime() > $expiry) {
            throw new BusinessException('授权碼已过期，到期時間：' . $expiry->format('Y-m-d'));
        }

        // 写入 .env 文件
        $this->updateEnvFile([
            'APP_EDITION' => $edition,
            'LICENSE_KEY' => $licenseKey,
            'LICENSE_TRIAL_ENABLED' => 'false',
        ]);

        // 清除缓存
        $this->licenseManager->clearCache();

        return $this->success([
            'edition' => $edition,
            'expiry'  => $expiry->format('Y-m-d'),
        ], '授权碼激活成功，請重啟服务使配置生效');
    }

    /**
     * 申请试用
     *
     * POST /admin/api/license/trial
     *
     * 请求參數：
     * - edition: 试用版本（commercial / enterprise）
     */
    public function startTrial(Request $request)
    {
        $edition = trim($request->post('edition', 'commercial'));

        if (!in_array($edition, ['commercial', 'enterprise'])) {
            throw new BusinessException('试用版本无效，必須為 commercial 或 enterprise');
        }

        // 如果已經在试用中，返回剩餘天數
        if ($this->licenseManager->isTrialMode() && !$this->licenseManager->isTrialExpired()) {
            return $this->success([
                'remaining_days' => $this->licenseManager->getTrialRemainingDays(),
            ], '试用已启动，剩餘 ' . $this->licenseManager->getTrialRemainingDays() . ' 天');
        }

        // 启动试用
        $this->licenseManager->startTrial();

        // 写入 .env
        $this->updateEnvFile([
            'APP_EDITION' => $edition,
            'LICENSE_TRIAL_ENABLED' => 'true',
        ]);

        // 清除缓存
        $this->licenseManager->clearCache();

        $trialDays = (int) config('license.trial.days', 14);

        return $this->success([
            'edition'        => $edition,
            'trial_days'     => $trialDays,
            'remaining_days' => $trialDays,
        ], "试用已启动，有效期 {$trialDays} 天，請重啟服务使配置生效");
    }

    /**
     * 获取功能對比表
     *
     * GET /admin/api/license/compare
     */
    public function compare(Request $request)
    {
        $features = config('license.features', []);

        $comparison = [];
        foreach ($features as $key => $feature) {
            $comparison[] = [
                'key'          => $key,
                'name'         => $feature['name'],
                'open_source'  => false,
                'commercial'   => $feature['min_edition'] === 'commercial',
                'enterprise'   => in_array($feature['min_edition'], ['commercial', 'enterprise']),
                'current'      => $this->licenseManager->hasFeature($key),
            ];
        }

        return $this->success([
            'features'  => $comparison,
            'editions'  => [
                'open_source' => ['name' => '开源版', 'price' => '免費', 'color' => '#67C23A'],
                'commercial'  => ['name' => '商业版', 'price' => '¥2,980/年', 'color' => '#E6A23C'],
                'enterprise'  => ['name' => '企业版', 'price' => '¥9,800/年', 'color' => '#F56C6C'],
            ],
            'current_edition' => $this->licenseManager->getEdition(),
        ]);
    }

    /**
     * 更新 .env 文件中的配置項
     */
    private function updateEnvFile(array $updates): void
    {
        $envFile = base_path() . '/.env';
        $envExample = base_path() . '/.env.example';

        if (!file_exists($envFile)) {
            // 如果 .env 不存在，從 .env.example 复制
            if (file_exists($envExample)) {
                copy($envExample, $envFile);
            } else {
                file_put_contents($envFile, '');
            }
        }

        $content = file_get_contents($envFile);
        $lines = explode("\n", $content);
        $updated = [];

        foreach ($lines as $i => $line) {
            $trimmed = trim($line);
            if (empty($trimmed) || $trimmed[0] === '#') {
                continue;
            }

            if (str_contains($line, '=')) {
                [$key] = explode('=', $line, 2);
                $key = trim($key);

                if (isset($updates[$key])) {
                    $lines[$i] = "{$key}={$updates[$key]}";
                    $updated[$key] = true;
                }
            }
        }

        // 添加未找到的新鍵
        foreach ($updates as $key => $value) {
            if (!isset($updated[$key])) {
                $lines[] = "{$key}={$value}";
            }
        }

        file_put_contents($envFile, implode("\n", $lines));
    }

    /**
     * 授权碼脫敏
     */
    private function maskLicenseKey(string $key): string
    {
        if (empty($key)) {
            return '';
        }
        $parts = explode('-', $key);
        if (count($parts) === 4) {
            return $parts[0] . '-' . $parts[1] . '-****-****';
        }
        return str_repeat('*', strlen($key));
    }
}
