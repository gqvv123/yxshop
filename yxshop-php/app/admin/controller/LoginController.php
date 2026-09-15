<?php

namespace app\admin\controller;

use support\Request;
use support\Response;
use app\service\AdminService;
use app\service\LoginLogService;
use app\service\JwtService;
use app\service\CaptchaService;
use app\service\SecurityService;
use app\exception\BusinessException;

class LoginController extends BaseController
{
    protected $loginLogService;
    protected $captchaService;
    protected $jwtService;
    protected $securityService;

    public function __construct()
    {
        parent::__construct();
        $this->loginLogService = new LoginLogService();
        $this->captchaService = new CaptchaService();
        $this->jwtService = new JwtService();
        $this->securityService = new SecurityService();
    }

    public function captcha(Request $request): Response
    {
        // 根据系统设置返回对应类型的验证码
        $captchaType = $this->getCaptchaType();
        if ($captchaType === 'puzzle') {
            $result = $this->captchaService->generatePuzzle();
            $result['captcha_type'] = 'puzzle';
            return $this->success($result);
        }
        $result = $this->captchaService->generate();
        $result['captcha_type'] = 'text';
        return $this->success($result);
    }

    /**
     * 拼图验证码单独生成接口
     */
    public function puzzleCaptcha(Request $request): Response
    {
        $result = $this->captchaService->generatePuzzle();
        return $this->success($result);
    }

    /**
     * 获取系统验证码类型
     */
    protected function getCaptchaType(): string
    {
        try {
            $config = $this->securityService->getSecurityConfig();
            return $config['captcha_type'] ?? 'text';
        } catch (\Throwable $e) {
            return 'text';
        }
    }

    public function login(Request $request): Response
    {
        $username = $request->post('username', '');
        $password = $request->post('password', '');
        $captchaCode = $request->post('captcha_code', '');
        $captchaUuid = $request->post('captcha_uuid', '');
        $puzzleUuid = $request->post('puzzle_uuid', '');
        $puzzleX = (float) $request->post('puzzle_x', -1);

        if (empty($username) || empty($password)) {
            throw new BusinessException('用户名和密码不能为空');
        }

        // 验证码校验：拼图或文本验证码至少提供一种
        if ($puzzleUuid && $puzzleX >= 0) {
            if (!$this->captchaService->verifyPuzzle($puzzleUuid, $puzzleX)) {
                throw new BusinessException('拼图验证失败，请重试');
            }
        } elseif ($captchaUuid && $captchaCode) {
            if (!$this->captchaService->verify($captchaUuid, $captchaCode)) {
                throw new BusinessException('验证码错误');
            }
        } else {
            throw new BusinessException('请完成验证码');
        }

        $admin = $this->adminService->findByUsername($username);
        if ($admin) {
            $lockMsg = $this->securityService->checkLoginLock($admin);
            if ($lockMsg) {
                throw new BusinessException($lockMsg);
            }
        }

        try {
            $admin = $this->adminService->login($username, $password);
        } catch (BusinessException $e) {
            if ($admin) {
                $failMsg = $this->securityService->handleLoginFailure($admin, $request->getRealIp());
                throw new BusinessException($failMsg);
            }
            throw $e;
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
                $admin->id, 'admin', $request->getRealIp(),
                LoginLogService::RESULT_SUCCESS, '登录成功(需修改密码)',
                $admin->app_id ?? 0, $admin->username ?? ''
            );

            return $this->success([
                'access_token' => $tempToken,
                'token_type' => 'Bearer',
                'expires_in' => 1800,
                'require_password_change' => true,
                'password_change_reason' => $expiry['status'],
            ]);
        }

        $token = $this->jwtService->generateToken([
            'user_id' => $admin->id,
            'username' => $admin->username,
            'type' => 'admin',
        ], $sessionTimeout);

        $this->loginLogService->recordLogin(
            $admin->id, 'admin', $request->getRealIp(),
            LoginLogService::RESULT_SUCCESS, '管理员登录成功',
            $admin->app_id ?? 0, $admin->username ?? ''
        );

        $response = [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $sessionTimeout,
        ];

        if ($expiry['status'] === 'warning') {
            $response['password_warning'] = [
                'days_remaining' => $expiry['days_remaining'],
                'message' => "密码将在{$expiry['days_remaining']}天后过期，请及时修改",
            ];
        }

        return $this->success($response);
    }

    public function logout(Request $request): Response
    {
        $authorization = $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.*)$/i', $authorization, $matches)) {
            $token = $matches[1];
            $this->jwtService->blacklistToken($token);
        }
        return $this->success(null, '退出成功');
    }

    public function userInfo(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $roles = [];
        $permissions = [];

        $role = $admin->role;
        if ($role) {
            $roles = [['id' => $role->id, 'name' => $role->role_name]];

            $authService = new \app\service\AdminAuthService();
            $permissions = $authService->getPermissionsByRole($role->id);
        }

        $menus = $this->adminService->getAdminMenus($admin);

        $securityConfig = $this->securityService->getSecurityConfig();
        $passwordExpiresAt = null;
        if ($admin->password_changed_at && $securityConfig['password_expire_days'] > 0) {
            $passwordExpiresAt = $admin->password_changed_at + $securityConfig['password_expire_days'] * 86400;
        }

        return $this->success([
            'user' => [
                'id' => $admin->id,
                'username' => $admin->username,
                'nickname' => $admin->nickname ?? $admin->username,
                'avatar' => $admin->avatar ?? '',
                'phone' => $admin->phone ?? '',
                'email' => $admin->email ?? '',
                'status' => $admin->status,
                'is_super_admin' => $admin->is_super_admin ?? 0,
                'backend_setting' => $admin->backend_setting ?? null,
                'role_name' => $role ? $role->role_name : '',
                'last_login_at' => $admin->last_login_at ?? null,
                'created_at' => $admin->created_at ?? null,
                'force_password_change' => (bool) ($admin->force_password_change ?? false),
                'password_expire_days' => $securityConfig['password_expire_days'],
                'password_expires_at' => $passwordExpiresAt,
            ],
            'roles' => $roles,
            'codes' => $permissions,
            'routers' => $menus,
        ]);
    }

    public function updateProfile(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $data = $request->post();

        if (isset($data['phone']) && !empty($data['phone'])) {
            if (!preg_match('/^1[3-9]\d{9}$/', $data['phone'])) {
                throw new BusinessException('手机号格式不正确');
            }
        }

        if (isset($data['email']) && !empty($data['email'])) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new BusinessException('邮箱格式不正确');
            }
        }

        $admin = $this->adminService->updateProfile($admin->id, $data);

        return $this->success([
            'id' => $admin->id,
            'username' => $admin->username,
            'nickname' => $admin->nickname ?? $admin->username,
            'avatar' => $admin->avatar ?? '',
            'phone' => $admin->phone ?? '',
            'email' => $admin->email ?? '',
        ], '更新成功');
    }

    public function changePassword(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $oldPassword = $request->post('old_password', '');
        $newPassword = $request->post('new_password', '');
        $confirmPassword = $request->post('confirm_password', '');

        if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
            throw new BusinessException('请填写完整的密码信息');
        }

        $complexityError = $this->securityService->validatePasswordComplexity($newPassword);
        if ($complexityError) {
            throw new BusinessException($complexityError);
        }

        if ($newPassword !== $confirmPassword) {
            throw new BusinessException('两次输入的密码不一致');
        }

        if ($this->securityService->checkPasswordHistory($admin->id, $newPassword)) {
            throw new BusinessException('不能使用最近使用过的密码');
        }

        $oldHash = $admin->password;
        $admin = $this->adminService->changePassword($admin->id, $oldPassword, $newPassword);

        $this->securityService->savePasswordHistory($admin, $oldHash);

        return $this->success(null, '密码修改成功');
    }

    public function forceChangePassword(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $newPassword = $request->post('new_password', '');
        $confirmPassword = $request->post('confirm_password', '');

        if (empty($newPassword) || empty($confirmPassword)) {
            throw new BusinessException('请填写完整的密码信息');
        }

        $complexityError = $this->securityService->validatePasswordComplexity($newPassword);
        if ($complexityError) {
            throw new BusinessException($complexityError);
        }

        if ($newPassword !== $confirmPassword) {
            throw new BusinessException('两次输入的密码不一致');
        }

        if ($this->securityService->checkPasswordHistory($admin->id, $newPassword)) {
            throw new BusinessException('不能使用最近使用过的密码');
        }

        $oldHash = $admin->password;
        $admin = $this->adminService->forceChangePassword($admin->id, $newPassword);

        $this->securityService->savePasswordHistory($admin, $oldHash);

        $authorization = $request->header('Authorization', '');
        if (preg_match('/^Bearer\s+(.*)$/i', $authorization, $matches)) {
            $this->jwtService->blacklistToken($matches[1]);
        }

        return $this->success(null, '密码修改成功，请重新登录');
    }

    public function twoFactorSetup(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $secret = $this->generateTOTPSecret();
        $admin = $this->adminService->setTwoFactorSecret($admin->id, $secret);

        $appName = urlencode(config('app.name', 'Yxwebai Admin'));
        $otpauthUrl = "otpauth://totp/{$appName}:{$admin->username}?secret={$secret}&issuer={$appName}";

        return $this->success([
            'secret' => $secret,
            'otpauth_url' => $otpauthUrl,
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=' . urlencode($otpauthUrl),
        ]);
    }

    public function twoFactorEnable(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $code = $request->post('code', '');
        if (empty($code)) {
            throw new BusinessException('请输入验证码');
        }

        if (empty($admin->two_factor_secret)) {
            throw new BusinessException('请先获取2FA密钥');
        }

        if (!$this->verifyTOTP($admin->two_factor_secret, $code)) {
            throw new BusinessException('验证码错误');
        }

        $this->adminService->enableTwoFactor($admin->id);

        $recoveryCodes = $this->generateRecoveryCodes();
        return $this->success([
            'recovery_codes' => $recoveryCodes,
        ], '2FA已启用');
    }

    public function twoFactorDisable(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $password = $request->post('password', '');
        if (empty($password) || !$admin->checkPassword($password)) {
            throw new BusinessException('密码不正确');
        }

        $this->adminService->disableTwoFactor($admin->id);

        return $this->success(null, '2FA已禁用');
    }

    public function twoFactorStatus(Request $request): Response
    {
        $admin = $request->admin ?? null;
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $status = $this->adminService->getTwoFactorStatus($admin->id);

        return $this->success($status);
    }

    private function generateTOTPSecret(): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= $chars[random_int(0, strlen($chars) - 1)];
        }
        return $secret;
    }

    private function verifyTOTP(string $secret, string $code, int $window = 1): bool
    {
        $timeSlice = floor(time() / 30);
        for ($i = -$window; $i <= $window; $i++) {
            $calculated = $this->calculateTOTP($secret, $timeSlice + $i);
            if (hash_equals($calculated, $code)) {
                return true;
            }
        }
        return false;
    }

    private function calculateTOTP(string $secret, float $timeSlice): string
    {
        $key = $this->base32Decode($secret);
        $time = pack('N', (int)$timeSlice);
        $hash = hash_hmac('SHA1', $time, $key, true);
        $offset = ord($hash[19]) & 0xf;
        $code = (
            ((ord($hash[$offset + 0]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        return str_pad((string)$code, 6, '0', STR_PAD_LEFT);
    }

    private function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    private function base32Decode(string $secret): string
    {
        $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32charsFlipped = array_flip(str_split($base32chars));
        $paddedSecret = str_pad($secret, (int)(ceil(strlen($secret) / 8) * 8), '=');
        $decoded = '';
        for ($i = 0; $i < strlen($paddedSecret); $i += 8) {
            $chunk = substr($paddedSecret, $i, 8);
            $bytes = 0;
            for ($j = 0; $j < 8; $j++) {
                if ($chunk[$j] !== '=') {
                    $bytes = ($bytes << 5) | $base32charsFlipped[$chunk[$j]];
                }
            }
            for ($j = 4; $j >= 0; $j--) {
                if ($i + $j < strlen($secret)) {
                    $decoded .= chr(($bytes >> ($j * 8)) & 0xff);
                }
            }
        }
        return $decoded;
    }
}
