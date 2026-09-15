<?php

namespace app\service;

use app\repository\AdminRepository;
use app\repository\UserRepository;
use app\repository\UserOauthRepository;
use app\model\Admin;
use app\model\User;
use app\model\UserOauth;
use app\model\LoginLog;
use app\exception\BusinessException;
use support\Log;

class WechatAuthService
{
    protected $wechatApiClient;
    protected $settingService;
    protected $securityService;
    protected $jwtService;
    protected $loginLogService;

    public function __construct()
    {
        $this->wechatApiClient = new WechatApiClient();
        $this->settingService = new SettingService();
        $this->securityService = new SecurityService();
        $this->jwtService = new JwtService();
        $this->loginLogService = new LoginLogService();
    }

    public function generateQrCode(string $scene, int $appId = 0): array
    {
        $config = $this->validateWechatConfig($scene);
        return $this->createScanSession($scene, $config, ['app_id' => $appId]);
    }

    public function createScanSession(string $scene, array $config, array $extra = []): array
    {
        $sceneKey = bin2hex(random_bytes(16));
        $state = bin2hex(random_bytes(8));

        $qrCodeUrl = $this->wechatApiClient->buildOAuthUrl(
            $config['appid'],
            $config['callback_url'],
            $state
        );

        $sessionData = array_merge([
            'scene' => $scene,
            'status' => 'pending',
            'state' => $state,
            'qr_code_url' => $qrCodeUrl,
            'created_at' => time(),
        ], $extra);

        try {
            \support\Redis::hMSet("wechat_scan:{$sceneKey}", $sessionData);
            \support\Redis::expire("wechat_scan:{$sceneKey}", 300);
            // state -> sceneKey 映射，O(1) 回调查找
            \support\Redis::setex("wechat_scan_state:{$state}", 300, $sceneKey);
        } catch (\Throwable $e) {
            Log::error('WechatAuthService Redis error', ['error' => $e->getMessage()]);
            throw new BusinessException('系统繁忙，请稍后重试');
        }

        return [
            'scene_key' => $sceneKey,
            'qr_code_url' => $qrCodeUrl,
            'expires_in' => 300,
        ];
    }

    public function getScanSession(string $sceneKey): ?array
    {
        try {
            $data = \support\Redis::hGetAll("wechat_scan:{$sceneKey}");
            return empty($data) ? null : $data;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function updateScanStatus(string $sceneKey, string $status, array $data = []): void
    {
        try {
            $update = array_merge(['status' => $status], $data);
            \support\Redis::hMSet("wechat_scan:{$sceneKey}", $update);
        } catch (\Throwable $e) {
            Log::error('WechatAuthService updateScanStatus error', ['error' => $e->getMessage()]);
        }
    }

    public function validateWechatConfig(string $scene): array
    {
        if (in_array($scene, ['admin_login', 'admin_bind'])) {
            // 优先从 .env 读取，回退到数据库设置
            $appid = getenv('WECHAT_ADMIN_APPID') ?: $this->settingService->getSetting('wechat_admin_appid', 0, '');
            $secret = getenv('WECHAT_ADMIN_SECRET') ?: $this->settingService->getSetting('wechat_admin_secret', 0, '');
            $callback_url = getenv('WECHAT_ADMIN_CALLBACK_URL') ?: $this->settingService->getSetting('wechat_admin_callback_url', 0, '');
        } else {
            $appid = getenv('WECHAT_OFFICIAL_APPID') ?: $this->settingService->getSetting('wechat_official_appid', 0, '');
            $secret = getenv('WECHAT_OFFICIAL_SECRET') ?: $this->settingService->getSetting('wechat_official_secret', 0, '');
            $callback_url = getenv('WECHAT_OFFICIAL_CALLBACK_URL') ?: $this->settingService->getSetting('wechat_official_callback_url', 0, '');
        }

        if (empty($appid) || empty($secret)) {
            throw new BusinessException('微信应用未配置', 4003, 'WECHAT_NOT_CONFIGURED');
        }

        return compact('appid', 'secret', 'callback_url');
    }

    public function pollScanStatus(string $sceneKey): array
    {
        $session = $this->getScanSession($sceneKey);
        if (!$session) {
            return ['status' => 'expired', 'message' => '二维码已过期'];
        }

        $status = $session['status'] ?? 'pending';
        $result = ['status' => $status];

        if ($status === 'confirmed') {
            $result['token_data'] = json_decode($session['token_data'] ?? 'null', true);
            if (isset($session['is_new_user'])) {
                $result['is_new_user'] = (bool) $session['is_new_user'];
            }
            if (isset($session['user_data'])) {
                $result['user'] = json_decode($session['user_data'], true);
            }
        } elseif ($status === 'failed') {
            $result['message'] = $session['error_message'] ?? '登录失败';
        }

        return $result;
    }

    public function handleOAuthCallback(string $code, string $state, string $fallbackScene): void
    {
        $sceneKey = null;
        try {
            $sceneKey = (string) \support\Redis::get("wechat_scan_state:{$state}");
        } catch (\Throwable $e) {}

        if (!$sceneKey) {
            Log::warning('WechatAuthService callback: state not found', ['state' => $state]);
            return;
        }

        // 从 session 中读取实际 scene（回调入口无法区分 admin_login / admin_bind）
        $session = $this->getScanSession($sceneKey);
        if (!$session) {
            Log::warning('WechatAuthService callback: session not found', ['state' => $state]);
            return;
        }

        $scene = (string) ($session['scene'] ?? $fallbackScene);
        $config = $this->validateWechatConfig($scene);

        try {
            $tokenResult = $this->wechatApiClient->getAccessTokenByCode($code, $config['appid'], $config['secret']);
            $openid = $tokenResult['openid'] ?? '';
            $unionid = $tokenResult['unionid'] ?? '';
            $accessToken = $tokenResult['access_token'] ?? '';

            if (empty($openid)) {
                throw new \RuntimeException('微信返回openid为空');
            }

            $userInfo = $this->wechatApiClient->getUserInfo($accessToken, $openid);
            $nickname = $userInfo['nickname'] ?? '';
            $avatarUrl = $userInfo['headimgurl'] ?? '';

            $this->updateScanStatus($sceneKey, 'scanned', [
                'openid' => $openid,
                'unionid' => $unionid,
                'nickname' => $nickname,
                'avatar_url' => $avatarUrl,
            ]);

            switch ($scene) {
                case 'user_login':
                    $result = $this->processUserLogin($openid, $unionid, $nickname, $avatarUrl, 0);
                    $this->updateScanStatus($sceneKey, 'confirmed', [
                        'token_data' => json_encode($result['token_data']),
                        'is_new_user' => $result['is_new_user'] ? '1' : '0',
                        'user_data' => json_encode($result['user'] ?? null),
                    ]);
                    break;

                case 'admin_login':
                    $result = $this->processAdminLogin($openid, '0.0.0.0');
                    $this->updateScanStatus($sceneKey, 'confirmed', [
                        'token_data' => json_encode($result),
                    ]);
                    break;

                case 'admin_bind':
                    $adminId = \support\Redis::hGet("wechat_scan:{$sceneKey}", 'admin_id');
                    $this->processAdminBind($openid, $nickname, $avatarUrl, (int) $adminId);
                    $this->updateScanStatus($sceneKey, 'confirmed');
                    break;
            }
        } catch (\Throwable $e) {
            Log::error('WechatAuthService callback error', ['error' => $e->getMessage()]);
            $this->updateScanStatus($sceneKey, 'failed', ['error_message' => $e->getMessage()]);
        }
    }

    public function processUserLogin(string $openid, ?string $unionid, string $nickname, string $avatarUrl, int $appId): array
    {
        $oauthRepo = new UserOauthRepository();
        $userRepo = new UserRepository();
        $oauth = $oauthRepo->query()
            ->where('oauth_type', UserOauth::OAUTH_WECHAT_QRCODE)
            ->where('openid', $openid)
            ->first();

        if ($oauth) {
            $user = $userRepo->find($oauth->user_id);
            if (!$user) {
                throw new BusinessException('用户不存在');
            }
            if (isset($user->deleted_at) && (int) $user->deleted_at > 0) {
                throw new BusinessException('账号已被禁用');
            }
            $user->last_login_at = time();
            $user->login_count = ($user->login_count ?? 0) + 1;
            $user->save();
            $isNewUser = false;
        } else {
            $user = $userRepo->create([
                'nickname' => $nickname ?: '微信用户',
                'avatar' => $avatarUrl,
                'source' => User::SOURCE_WECHAT_QRCODE,
                'app_id' => $appId,
                'status' => 1,
                'last_login_at' => time(),
                'login_count' => 1,
            ]);

            $oauth = $oauthRepo->create([
                'user_id' => $user->id,
                'oauth_type' => UserOauth::OAUTH_WECHAT_QRCODE,
                'openid' => $openid,
                'unionid' => $unionid,
                'nickname' => $nickname,
                'avatar' => $avatarUrl,
                'app_id' => $appId,
            ]);

            $isNewUser = true;
        }

        $token = $this->jwtService->generateToken([
            'user_id' => $user->id,
            'username' => $user->nickname,
            'type' => 'user',
        ]);

        return [
            'token_data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => config('jwt.expire_time', 7200),
            ],
            'is_new_user' => $isNewUser,
            'user' => [
                'id' => (string) $user->id,
                'nickname' => $user->nickname,
                'avatar' => $user->avatar,
            ],
        ];
    }

    public function processAdminLogin(string $openid, string $ip): array
    {
        $adminRepo = new AdminRepository();
        $admin = $adminRepo->query()
            ->where('wechat_openid', $openid)->first();
        if (!$admin) {
            throw new BusinessException('该微信未绑定管理员账号');
        }

        if (isset($admin->deleted_at) && (int) $admin->deleted_at > 0) {
            throw new BusinessException('账号已被禁用');
        }

        if ((int) ($admin->status ?? 1) !== 1) {
            throw new BusinessException('账号已被禁用');
        }

        $lockMsg = $this->securityService->checkLoginLock($admin);
        if ($lockMsg) {
            throw new BusinessException($lockMsg);
        }

        $this->securityService->handleLoginSuccess($admin);

        $securityConfig = $this->securityService->getSecurityConfig();
        $sessionTimeout = $securityConfig['session_timeout'] * 60;
        $expiry = $this->securityService->checkPasswordExpiry($admin);

        if (in_array($expiry['status'], ['first_login', 'expired', 'force_change'])) {
            $tempToken = $this->jwtService->generateTempToken([
                'user_id' => $admin->id,
                'username' => $admin->username,
                'type' => 'admin',
            ], 'password_change', 1800);

            $this->loginLogService->recordLogin(
                $admin->id, 'admin', $ip,
                LoginLog::RESULT_SUCCESS, '微信扫码登录(需修改密码)',
                $admin->app_id ?? 0, $admin->username ?? '',
                LoginLog::LOGIN_TYPE_WECHAT
            );

            return [
                'access_token' => $tempToken,
                'token_type' => 'Bearer',
                'expires_in' => 1800,
                'require_password_change' => true,
                'password_change_reason' => $expiry['status'],
            ];
        }

        $token = $this->jwtService->generateToken([
            'user_id' => $admin->id,
            'username' => $admin->username,
            'type' => 'admin',
        ], $sessionTimeout);

        $this->loginLogService->recordLogin(
            $admin->id, 'admin', $ip,
            LoginLog::RESULT_SUCCESS, '微信扫码登录成功',
            $admin->app_id ?? 0, $admin->username ?? '',
            LoginLog::LOGIN_TYPE_WECHAT
        );

        $result = [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $sessionTimeout,
        ];

        if ($expiry['status'] === 'warning') {
            $result['password_warning'] = [
                'days_remaining' => $expiry['days_remaining'],
                'message' => "密码将在{$expiry['days_remaining']}天后过期",
            ];
        }

        return $result;
    }

    public function processAdminBind(string $openid, string $nickname, string $avatarUrl, int $adminId): array
    {
        $adminRepo = new AdminRepository();
        $existing = $adminRepo->query()->where('wechat_openid', $openid)->where('id', '!=', $adminId)->first();
        if ($existing) {
            throw new BusinessException('该微信已绑定其他管理员账号');
        }

        $admin = $adminRepo->find($adminId);
        if (!$admin) {
            throw new BusinessException('管理员不存在');
        }

        $admin->wechat_openid = $openid;
        $admin->wechat_bound_at = time();
        $admin->wechat_nickname = $nickname;
        $admin->wechat_avatar = $avatarUrl;
        $admin->save();

        return ['success' => true];
    }

    public function bindWechat(int $adminId, string $password): array
    {
        $adminRepo = new AdminRepository();
        $admin = $adminRepo->find($adminId);
        if (!$admin) {
            throw new BusinessException('管理员不存在');
        }

        if (!$admin->checkPassword($password)) {
            throw new BusinessException('密码不正确');
        }

        if (!empty($admin->wechat_openid)) {
            throw new BusinessException('已绑定微信，请先解绑');
        }

        $config = $this->validateWechatConfig('admin_bind');
        return $this->createScanSession('admin_bind', $config, ['admin_id' => $adminId]);
    }

    public function unbindWechat(int $adminId, string $password): array
    {
        $adminRepo = new AdminRepository();
        $admin = $adminRepo->find($adminId);
        if (!$admin) {
            throw new BusinessException('管理员不存在');
        }

        if (!$admin->checkPassword($password)) {
            throw new BusinessException('密码不正确');
        }

        if (empty($admin->wechat_openid)) {
            throw new BusinessException('未绑定微信');
        }

        $admin->wechat_openid = null;
        $admin->wechat_bound_at = null;
        $admin->wechat_nickname = null;
        $admin->wechat_avatar = null;
        $admin->save();

        return ['success' => true];
    }

    public function getBindStatus(int $adminId): array
    {
        $adminRepo = new AdminRepository();
        $admin = $adminRepo->find($adminId);
        if (!$admin) {
            throw new BusinessException('管理员不存在');
        }

        return [
            'is_bound' => !empty($admin->wechat_openid),
            'bound_at' => $admin->wechat_bound_at ?? null,
            'wechat_nickname' => $admin->wechat_nickname ?? '',
            'wechat_avatar' => $admin->wechat_avatar ?? '',
        ];
    }

    public function mockCallback(string $sceneKey, string $openid, string $nickname = '', string $avatarUrl = ''): array
    {
        if (!$this->wechatApiClient->isMockMode()) {
            throw new BusinessException('模拟模式未开启');
        }

        $session = $this->getScanSession($sceneKey);
        if (!$session) {
            throw new BusinessException('扫码会话不存在或已过期');
        }

        $scene = $session['scene'] ?? '';
        $this->updateScanStatus($sceneKey, 'scanned', [
            'openid' => $openid,
            'nickname' => $nickname ?: '模拟用户',
            'avatar_url' => $avatarUrl,
        ]);

        switch ($scene) {
            case 'user_login':
                $result = $this->processUserLogin($openid, '', $nickname ?: '模拟用户', $avatarUrl, 0);
                $this->updateScanStatus($sceneKey, 'confirmed', [
                    'token_data' => json_encode($result['token_data']),
                    'is_new_user' => $result['is_new_user'] ? '1' : '0',
                    'user_data' => json_encode($result['user'] ?? null),
                ]);
                break;

            case 'admin_login':
                $result = $this->processAdminLogin($openid, '127.0.0.1');
                $this->updateScanStatus($sceneKey, 'confirmed', [
                    'token_data' => json_encode($result),
                ]);
                break;

            case 'admin_bind':
                $adminId = $session['admin_id'] ?? 0;
                $this->processAdminBind($openid, $nickname ?: '模拟用户', $avatarUrl, (int) $adminId);
                $this->updateScanStatus($sceneKey, 'confirmed');
                break;
        }

        return ['status' => 'confirmed'];
    }
}