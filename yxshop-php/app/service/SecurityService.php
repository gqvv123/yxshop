<?php

namespace app\service;

use app\model\Admin;
use app\repository\AdminRepository;
use app\repository\AdminPasswordHistoryRepository;
use app\repository\LoginLogRepository;
use app\exception\BusinessException;

/**
 * 安全服务
 *
 * 4 层架构：所有数据访问通过对应 Repository
 */
class SecurityService
{
    const DEFAULT_SECURITY_CONFIG = [
        'login_lock_threshold' => 5,
        'login_lock_duration' => 30,
        'password_min_length' => 8,
        'password_require_uppercase' => 0,
        'password_require_lowercase' => 1,
        'password_require_digit' => 1,
        'password_require_special' => 0,
        'password_expire_days' => 90,
        'password_history_count' => 3,
        'session_timeout' => 120,
        'captcha_type' => 'text',
    ];

    protected $settingService;
    protected AdminRepository $adminRepository;
    protected AdminPasswordHistoryRepository $passwordHistoryRepository;
    protected LoginLogRepository $loginLogRepository;

    public function __construct()
    {
        $this->settingService = new SettingService();
        $this->adminRepository = new AdminRepository();
        $this->passwordHistoryRepository = new AdminPasswordHistoryRepository();
        $this->loginLogRepository = new LoginLogRepository();
    }

    public function getSecurityConfig(): array
    {
        try {
            $cached = \support\Redis::get('security_config');
            if ($cached) {
                return json_decode($cached, true);
            }
        } catch (\Throwable $e) {}

        $result = self::DEFAULT_SECURITY_CONFIG;
        try {
            $settings = $this->settingService->getGroupSettings('security', 0);
            if (!empty($settings)) {
                foreach (array_keys(self::DEFAULT_SECURITY_CONFIG) as $key) {
                    if (isset($settings[$key])) {
                        if ($key === 'captcha_type') {
                            $result[$key] = $settings[$key];
                        } else {
                            $result[$key] = (int) $settings[$key];
                        }
                    }
                }
            }
        } catch (\Throwable $e) {}

        try {
            \support\Redis::setex('security_config', 3600, json_encode($result));
        } catch (\Throwable $e) {}

        return $result;
    }

    public function saveSecurityConfig(array $data, int $operatorId): void
    {
        $ranges = [
            'login_lock_threshold' => [3, 10],
            'login_lock_duration' => [5, 1440],
            'password_min_length' => [6, 32],
            'password_require_uppercase' => [0, 1],
            'password_require_lowercase' => [0, 1],
            'password_require_digit' => [0, 1],
            'password_require_special' => [0, 1],
            'password_expire_days' => [0, 365],
            'password_history_count' => [0, 24],
            'session_timeout' => [15, 1440],
        ];

        $descriptions = [
            'login_lock_threshold' => '登录失败锁定阈值(次)',
            'login_lock_duration' => '登录锁定时长(分钟)',
            'password_min_length' => '密码最小长度',
            'password_require_uppercase' => '密码必须包含大写字母',
            'password_require_lowercase' => '密码必须包含小写字母',
            'password_require_digit' => '密码必须包含数字',
            'password_require_special' => '密码必须包含特殊字符',
            'password_expire_days' => '密码有效期天数(0=不限制)',
            'password_history_count' => '密码历史保留次数(0=不限制)',
            'session_timeout' => '会话超时时间(分钟)',
            'captcha_type' => '登录验证码类型(text=数字字母,puzzle=拼图滑块)',
        ];

        // 处理 captcha_type (字符串类型)
        if (isset($data['captcha_type'])) {
            $captchaType = $data['captcha_type'];
            if (!in_array($captchaType, ['text', 'puzzle'])) {
                throw new BusinessException('验证码类型只能为 text 或 puzzle');
            }
            $this->settingService->setSetting('captcha_type', $captchaType, 'string', 'security', $descriptions['captcha_type'], 0);
        }

        foreach ($data as $key => $value) {
            if (!isset($ranges[$key])) continue;
            $val = (int) $value;
            $min = $ranges[$key][0];
            $max = $ranges[$key][1];
            if ($val < $min || $val > $max) {
                throw new BusinessException("{$descriptions[$key]}取值范围为{$min}-{$max}");
            }
            $this->settingService->setSetting($key, (string) $val, 'integer', 'security', $descriptions[$key], 0);
        }

        $this->clearSecurityCache();
        $this->recordAuditLog($operatorId, LoginLogService::LOGIN_TYPE_SECURITY_CHANGE, '安全策略配置变更');
    }

    public function clearSecurityCache(): void
    {
        try {
            \support\Redis::del('security_config');
        } catch (\Throwable $e) {}
    }

    public function checkLoginLock(Admin $admin): ?string
    {
        if (!$admin->locked_until) return null;

        if ($admin->locked_until <= time()) {
            $admin->login_fail_count = 0;
            $admin->locked_until = null;
            $admin->save();
            return null;
        }

        $remaining = (int) ceil(($admin->locked_until - time()) / 60);
        return "账号已锁定，请{$remaining}分钟后重试";
    }

    public function handleLoginFailure(Admin $admin, string $ip): string
    {
        $admin->login_fail_count = ($admin->login_fail_count ?? 0) + 1;
        $config = $this->getSecurityConfig();
        $threshold = $config['login_lock_threshold'];
        $duration = $config['login_lock_duration'];

        if ($admin->login_fail_count >= $threshold) {
            $admin->locked_until = time() + $duration * 60;
            $admin->save();

            $this->loginLogRepository->record(
                $admin->id, 'admin', $ip, LoginLogService::RESULT_FAILED,
                "密码错误次数过多，账号已锁定{$duration}分钟",
                $admin->app_id ?? 0, $admin->username ?? '',
                LoginLogService::LOGIN_TYPE_PASSWORD, '账号锁定'
            );

            return "密码错误次数过多，账号已锁定{$duration}分钟";
        }

        $admin->save();
        $remaining = $threshold - $admin->login_fail_count;

        $this->loginLogRepository->record(
            $admin->id, 'admin', $ip, LoginLogService::RESULT_FAILED,
            "用户名或密码错误，还可尝试{$remaining}次",
            $admin->app_id ?? 0, $admin->username ?? '',
            LoginLogService::LOGIN_TYPE_PASSWORD, '密码错误'
        );

        return "用户名或密码错误，还可尝试{$remaining}次";
    }

    public function handleLoginSuccess(Admin $admin): void
    {
        $admin->login_fail_count = 0;
        $admin->locked_until = null;
        $admin->last_login_at = time();
        $admin->save();
    }

    public function validatePasswordComplexity(string $password): ?string
    {
        $config = $this->getSecurityConfig();

        if (mb_strlen($password) < $config['password_min_length']) {
            return "密码长度不能少于{$config['password_min_length']}位";
        }
        if ($config['password_require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            return '密码必须包含大写字母';
        }
        if ($config['password_require_lowercase'] && !preg_match('/[a-z]/', $password)) {
            return '密码必须包含小写字母';
        }
        if ($config['password_require_digit'] && !preg_match('/[0-9]/', $password)) {
            return '密码必须包含数字';
        }
        if ($config['password_require_special'] && !preg_match('/[!@#$%^&*()_+\-=\[\]{}|;\':",.<>?\/`~]/', $password)) {
            return '密码必须包含特殊字符';
        }

        return null;
    }

    public function checkPasswordHistory(int $adminId, string $newPassword): bool
    {
        $config = $this->getSecurityConfig();
        $count = $config['password_history_count'];
        if ($count <= 0) return false;

        $histories = $this->passwordHistoryRepository->getRecentByAdmin($adminId, $count);

        foreach ($histories as $history) {
            if (password_verify($newPassword, $history->password_hash)) {
                return true;
            }
        }

        return false;
    }

    public function savePasswordHistory(Admin $admin, string $oldPasswordHash): void
    {
        $config = $this->getSecurityConfig();
        if ($config['password_history_count'] <= 0) return;

        $this->passwordHistoryRepository->create([
            'admin_id' => $admin->id,
            'password_hash' => $oldPasswordHash,
            'created_at' => time(),
        ]);

        $this->cleanOldHistories($admin->id);
    }

    public function cleanOldHistories(int $adminId): void
    {
        $config = $this->getSecurityConfig();
        $maxCount = $config['password_history_count'];
        if ($maxCount <= 0) return;

        $oldestIds = $this->passwordHistoryRepository->getExcessOldIds($adminId, $maxCount);
        if (!empty($oldestIds)) {
            $this->passwordHistoryRepository->deleteByIds($oldestIds);
        }
    }

    public function checkPasswordExpiry(Admin $admin): array
    {
        if (!$admin->password_changed_at) {
            return ['status' => 'first_login', 'reason' => 'first_login'];
        }

        if ($admin->force_password_change) {
            return ['status' => 'force_change', 'reason' => 'force_change'];
        }

        $config = $this->getSecurityConfig();
        $expireDays = $config['password_expire_days'];
        if ($expireDays <= 0) {
            return ['status' => 'normal'];
        }

        $remaining = $expireDays - (time() - $admin->password_changed_at) / 86400;

        if ($remaining <= 0) {
            return ['status' => 'expired', 'reason' => 'expired'];
        }

        if ($remaining <= 7) {
            return ['status' => 'warning', 'days_remaining' => (int) ceil($remaining)];
        }

        return ['status' => 'normal'];
    }

    public function unlockAdmin(int $adminId, int $operatorId): void
    {
        $admin = $this->adminRepository->find($adminId);
        if (!$admin) {
            throw new BusinessException('管理员不存在');
        }

        $admin->locked_until = null;
        $admin->login_fail_count = 0;
        $admin->save();

        $this->recordAuditLog($operatorId, LoginLogService::LOGIN_TYPE_ADMIN_UNLOCK, "解锁管理员: {$admin->username}");
    }

    public function recordAuditLog(int $operatorId, int $loginType, string $message): void
    {
        $this->loginLogRepository->record(
            $operatorId, 'admin', '0.0.0.0',
            LoginLogService::RESULT_SUCCESS, $message,
            0, '', $loginType
        );
    }
}
