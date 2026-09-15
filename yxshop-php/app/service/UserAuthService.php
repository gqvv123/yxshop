<?php

namespace app\service;

use app\model\User;
use app\repository\UserRepository;
use support\Db;
use support\Redis;
use Exception;
/**
 * 前台/小程序 用户认证服务
 *
 * 与权限管理用的 AuthService（admin 权限树）相互独立。
 * 所有方法返回 ['success' => bool, 'data' => mixed, 'message' => string]
 * 与 v1/v2 控制器的契约一致；失败原因通过 message 透出，不再 throw。
 */
class UserAuthService extends BaseService
{
    /** @var JwtService */
    protected $jwt;

    /** Redis key prefix（黑名单/重置 token） */
    private const RESET_TOKEN_PREFIX = 'user:reset_token:';
    private const TOKEN_BLACKLIST_PREFIX = 'user:jwt_blacklist:';
    private const REFRESH_TOKEN_PREFIX = 'user:refresh_token:';

    public function __construct(?UserRepository $repository = null)
    {
        parent::__construct($repository ?? new UserRepository());
        $this->jwt = new JwtService();
    }

    /**
     * 用户登录（用户名/手机号/邮箱 + 密码）
     */
    public function login(string $identifier, string $password, int $appId = 0, string $ip = ''): array
    {
        try {
            if ($identifier === '' || $password === '') {
                return $this->fail('账号或密码不能为空');
            }

            $user = $this->lookupUser($identifier, $appId);
            if (!$user) {
                return $this->fail('账号或密码错误');
            }

            if ((int) ($user->deleted_at ?? 0) === 1) {
                return $this->fail('账号已被禁用');
            }

            if (empty($user->password)) {
                return $this->fail('账号未设置密码，请使用其它方式登录');
            }

            // 优先 password_hash，兼容历史 MD5
            if (!yxmall_pass_verify($password, $user->password)) {
                $this->logWarning('用户登录密码错误', ['identifier' => $identifier, 'ip' => $ip]);
                return $this->fail('账号或密码错误');
            }

            // 兼容旧哈希：自动升级为 password_hash
            if (yxmall_pass_needs_rehash($user->password)) {
                $user->password = yxmall_pass($password);
            }

            $user->last_login_at = time();
            if ($ip !== '') {
                $user->last_login_ip = $ip;
            }
            $user->login_count = (int) ($user->login_count ?? 0) + 1;
            $user->save();

            $tokens = $this->issueTokens($user);

            return $this->ok([
                'token' => $tokens['token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_in' => $tokens['expires_in'],
                'user' => $this->presentUser($user),
            ], '登录成功');
        } catch (Exception $e) {
            $this->logError('用户登录异常', ['error' => $e->getMessage()]);
            return $this->fail('登录失败，请稍后再试');
        }
    }

    /**
     * 用户注册
     */
    public function register(array $data): array
    {
        try {
            $required = ['username', 'password'];
            foreach ($required as $key) {
                if (empty($data[$key])) {
                    return $this->fail("{$key} 不能为空");
                }
            }

            $appId = (int) ($data['app_id'] ?? 0);
            $username = (string) $data['username'];
            $password = (string) $data['password'];

            if (strlen($password) < 6) {
                return $this->fail('密码长度至少 6 位');
            }

            if ($this->lookupUser($username, $appId)) {
                return $this->fail('账号已存在');
            }

            if (!empty($data['phone']) && $this->repository->findByPhone($data['phone'], $appId)) {
                return $this->fail('手机号已被占用');
            }

            // 推荐人绑定：支持 ref 参数（推荐人用户ID）
            $referrerId = $this->resolveReferrerId($data['ref'] ?? ($data['referrer_id'] ?? 0), $appId);

            /** @var User $user */
            $user = $this->transaction(function () use ($data, $username, $password, $appId, $referrerId) {
                $payload = [
                    // openid 不写入：账号密码注册的用户没有微信 openid，
                    // 表已改为允许 NULL，多个本地用户的 NULL 不会冲突唯一索引
                    'phone' => $data['phone'] ?? '',
                    'nickname' => $data['nickname'] ?? $username,
                    'avatar_url' => $data['avatar_url'] ?? '',
                    'app_id' => $appId,
                    'password' => yxmall_pass($password),
                    'level_id' => 1,
                    'agio' => 10.00,
                    'is_dealer' => 0,
                    'deleted_at' => 0,
                    'created_at' => time(),
                    'updated_at' => time(),
                ];
                if (!empty($data['email'])) {
                    $payload['email'] = $data['email'];
                }
                if ($referrerId > 0) {
                    $payload['referrer_id'] = $referrerId;
                }
                return $this->repository->create($payload);
            });

            // 异步写入推荐关系记录（不影响注册主流程）
            if ($referrerId > 0) {
                $this->createRefereeRecord($referrerId, (int) $user->id, $appId);
            }

            $tokens = $this->issueTokens($user);

            return $this->ok([
                'token' => $tokens['token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_in' => $tokens['expires_in'],
                'user' => $this->presentUser($user),
            ], '注册成功');
        } catch (Exception $e) {
            $this->logError('用户注册异常', ['error' => $e->getMessage()]);
            return $this->fail('注册失败：' . $e->getMessage());
        }
    }

    /**
     * 用户登出
     * 接受 userId（来自中间件）或 raw token，将其加入黑名单直至自然过期
     */
    public function logout($userIdOrToken): array
    {
        try {
            // 若传入 token，则解析后加黑名单
            if (is_string($userIdOrToken) && strlen($userIdOrToken) > 32) {
                $payload = $this->jwt->validateToken($userIdOrToken);
                if (!empty($payload['success'])) {
                    $ttl = max(60, ($payload['exp'] ?? time()) - time());
                    $this->blacklistToken($userIdOrToken, $ttl);
                }
            }

            // 若是 userId，可清理 refresh_token
            if (is_numeric($userIdOrToken)) {
                $this->revokeRefreshTokens((int) $userIdOrToken);
            }

            return $this->ok(null, '已登出');
        } catch (Exception $e) {
            $this->logError('登出异常', ['error' => $e->getMessage()]);
            return $this->fail('登出失败');
        }
    }

    /**
     * 刷新 token
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            if ($refreshToken === '') {
                return $this->fail('refresh token 不能为空');
            }

            $payload = $this->jwt->validateToken($refreshToken);
            if (empty($payload['success'])) {
                return $this->fail('refresh token 无效');
            }

            $userId = (int) ($payload['data']['user_id'] ?? 0);
            $purpose = (string) ($payload['data']['purpose'] ?? '');
            if ($userId <= 0 || $purpose !== 'refresh') {
                return $this->fail('refresh token 无效');
            }

            // 校验 refresh token 是否在白名单（防止已撤销）
            if (!$this->isRefreshTokenActive($userId, $refreshToken)) {
                return $this->fail('refresh token 已失效，请重新登录');
            }

            $user = $this->repository->find($userId);
            if (!$user || (int) ($user->deleted_at ?? 0) === 1) {
                return $this->fail('用户不可用');
            }

            $tokens = $this->issueTokens($user);
            return $this->ok([
                'token' => $tokens['token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_in' => $tokens['expires_in'],
            ], 'token 已刷新');
        } catch (Exception $e) {
            $this->logError('刷新 token 异常', ['error' => $e->getMessage()]);
            return $this->fail('刷新失败');
        }
    }

    /**
     * 忘记密码：生成一次性重置 token，返回给前端用于发送邮件/短信
     */
    public function forgotPassword(string $emailOrPhone, int $appId = 0): array
    {
        try {
            if ($emailOrPhone === '') {
                return $this->fail('账号不能为空');
            }
            $user = $this->lookupUser($emailOrPhone, $appId);
            // 安全考虑：不暴露用户是否存在，统一返回成功
            if (!$user) {
                return $this->ok(null, '若账号存在，重置链接已发送');
            }

            $token = bin2hex(random_bytes(24));
            $ttl = 1800; // 30 分钟
            $this->storeResetToken($token, $user->id, $ttl);

            // 这里仅返回 token；真正发送由通知服务异步处理
            $this->logInfo('发起密码重置', ['user_id' => $user->id]);

            return $this->ok([
                'reset_token' => $token,
                'expires_in' => $ttl,
            ], '若账号存在，重置链接已发送');
        } catch (Exception $e) {
            $this->logError('忘记密码异常', ['error' => $e->getMessage()]);
            return $this->fail('请稍后再试');
        }
    }

    /**
     * 重置密码
     */
    public function resetPassword(string $token, string $newPassword): array
    {
        try {
            if ($token === '' || $newPassword === '') {
                return $this->fail('参数不能为空');
            }
            if (strlen($newPassword) < 6) {
                return $this->fail('密码长度至少 6 位');
            }

            $userId = $this->consumeResetToken($token);
            if (!$userId) {
                return $this->fail('重置链接已失效');
            }

            $user = $this->repository->find($userId);
            if (!$user) {
                return $this->fail('用户不存在');
            }

            $user->password = yxmall_pass($newPassword);
            $user->save();

            // 同时撤销该用户所有 refresh_token，强制重新登录
            $this->revokeRefreshTokens($userId);

            return $this->ok(null, '密码重置成功');
        } catch (Exception $e) {
            $this->logError('重置密码异常', ['error' => $e->getMessage()]);
            return $this->fail('重置失败');
        }
    }

    /**
     * 微信小程序一键登录
     *
     * 流程：
     *   1) 前端 wx.login() 拿 code
     *   2) 调本接口，后端用 code + appid + secret 调用微信 jscode2session
     *   3) 拿到 openid / unionid / session_key 后入库或更新 user
     *   4) 签发 JWT
     *
     * 入参：
     *   - code           必填，wx.login 返回的临时登录凭证
     *   - app_id         可选，多租户场景的业务 app_id（与微信 appid 不同）
     *   - encrypted_data 可选，wx.getUserInfo 返回的加密用户信息（解密用 session_key）
     *   - iv             可选，配合 encrypted_data 使用
     *   - profile        可选，[ 'nickname' => ..., 'avatar_url' => ... ]
     *                    用于 wx.getUserProfile 替代 getUserInfo 的场景
     */
    public function wxLogin(array $payload): array
    {
        try {
            $code = (string) ($payload['code'] ?? '');
            if ($code === '') {
                return $this->fail('缺少 wx.login code');
            }

            $appId = (int) ($payload['app_id'] ?? 0);
            $session = $this->code2Session($code);

            if (empty($session['openid'])) {
                $msg = $session['errmsg'] ?? '微信换取 openid 失败';
                $this->logError('code2session 失败', ['code' => $code, 'resp' => $session]);
                return $this->fail($msg);
            }

            $openid = (string) $session['openid'];
            $unionid = (string) ($session['unionid'] ?? '');
            $sessionKey = (string) ($session['session_key'] ?? '');

            // 查询或创建 user
            $user = $this->repository->findByOpenId($openid, $appId);
            $isNew = false;

            if (!$user) {
                $isNew = true;

                // 推荐人绑定：小程序场景 ref 参数通过分享链接传入
                $referrerId = $this->resolveReferrerId($payload['ref'] ?? ($payload['referrer_id'] ?? 0), $appId);

                /** @var User $user */
                $user = $this->transaction(function () use ($openid, $unionid, $appId, $payload, $referrerId) {
                    $profile = (array) ($payload['profile'] ?? []);
                    $data = [
                        'openid' => $openid,
                        'unionid' => $unionid,
                        'app_id' => $appId,
                        'nickname' => $profile['nickname'] ?? '微信用户',
                        'avatar_url' => $profile['avatar_url'] ?? '',
                        'level_id' => 1,
                        'agio' => 10.00,
                        'is_dealer' => 0,
                        'deleted_at' => 0,
                        'source' => User::SOURCE_MINIPROGRAM,
                        'created_at' => time(),
                        'updated_at' => time(),
                    ];
                    if ($referrerId > 0) {
                        $data['referrer_id'] = $referrerId;
                    }
                    return $this->repository->create($data);
                });

                // 写入推荐关系记录
                if ($referrerId > 0) {
                    $this->createRefereeRecord($referrerId, (int) $user->id, $appId);
                }
            } else {
                // 已存在用户：补全 unionid（早期用户可能没记录）
                if ($unionid !== '' && empty($user->unionid)) {
                    $user->unionid = $unionid;
                }
                // 若前端带了 profile（getUserProfile 主动授权），同步昵称头像
                $profile = (array) ($payload['profile'] ?? []);
                if (!empty($profile['nickname']) && empty($user->nickname)) {
                    $user->nickname = $profile['nickname'];
                }
                if (!empty($profile['avatar_url']) && empty($user->avatar_url)) {
                    $user->avatar_url = $profile['avatar_url'];
                }
            }

            if ((int) ($user->deleted_at ?? 0) === 1) {
                return $this->fail('账号已被禁用');
            }

            $user->last_login_at = time();
            if (!empty($payload['ip'])) {
                $user->last_login_ip = (string) $payload['ip'];
            }
            $user->login_count = (int) ($user->login_count ?? 0) + 1;
            $user->save();

            // 缓存 session_key 供后续手机号/敏感数据解密使用（30 分钟过期）
            if ($sessionKey !== '') {
                $this->cacheSessionKey($user->id, $sessionKey);
            }

            $tokens = $this->issueTokens($user);

            return $this->ok([
                'token' => $tokens['token'],
                'refresh_token' => $tokens['refresh_token'],
                'expires_in' => $tokens['expires_in'],
                'is_new_user' => $isNew,
                'user' => $this->presentUser($user),
            ], $isNew ? '注册成功' : '登录成功');
        } catch (Exception $e) {
            $this->logError('微信登录异常', ['error' => $e->getMessage()]);
            return $this->fail('微信登录失败，请稍后再试');
        }
    }

    /**
     * 同步微信前端获取的头像/昵称（适配新版 chooseAvatar / nickname input）
     */
    public function updateWxProfile(int $userId, array $profile): array
    {
        try {
            $user = $this->repository->find($userId);
            if (!$user) {
                return $this->fail('用户不存在');
            }
            if (isset($profile['nickname']) && $profile['nickname'] !== '') {
                $user->nickname = (string) $profile['nickname'];
            }
            if (isset($profile['avatar_url']) && $profile['avatar_url'] !== '') {
                $user->avatar_url = (string) $profile['avatar_url'];
            }
            if (isset($profile['gender'])) {
                $user->gender = (int) $profile['gender'];
            }
            $user->updated_at = time();
            $user->save();
            return $this->ok($this->presentUser($user), '已更新');
        } catch (Exception $e) {
            $this->logError('更新微信资料失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return $this->fail('更新失败');
        }
    }

    /**
     * 微信手机号一键绑定
     *
     * 支持两种入参（优先 code 方式，微信官方推荐）：
     *   A) ['user_id' => , 'code' => '...']
     *      —— button open-type="getPhoneNumber" 返回的动态令牌，
     *         后端调 getuserphonenumber 直接换手机号，不依赖 session_key。
     *   B) ['user_id' => , 'encrypted_data' => , 'iv' => ]
     *      —— 旧版，用登录时缓存的 session_key 解密。
     */
    public function bindWxPhone(int $userId, array $payload): array
    {
        try {
            $user = $this->repository->find($userId);
            if (!$user) {
                return $this->fail('用户不存在');
            }

            $phoneInfo = null;

            // 方式 A：code 换手机号
            if (!empty($payload['code'])) {
                $phoneInfo = $this->getPhoneByCode((string) $payload['code']);
            }

            // 方式 B：session_key 解密
            if ($phoneInfo === null && !empty($payload['encrypted_data']) && !empty($payload['iv'])) {
                $sessionKey = $this->getCachedSessionKey($userId);
                if (!$sessionKey) {
                    return $this->fail('登录态已过期，请重新登录后再绑定');
                }
                $phoneInfo = $this->decryptWxData(
                    $sessionKey,
                    (string) $payload['iv'],
                    (string) $payload['encrypted_data']
                );
            }

            if (!is_array($phoneInfo) || empty($phoneInfo['purePhoneNumber'] ?? $phoneInfo['phoneNumber'] ?? '')) {
                return $this->fail('获取手机号失败');
            }

            $phone = (string) ($phoneInfo['purePhoneNumber'] ?? $phoneInfo['phoneNumber']);

            // 同一 app 下手机号唯一性检查
            $appId = (int) ($user->app_id ?? 0);
            $existing = $this->repository->findByPhone($phone, $appId);
            if ($existing && (int) $existing->id !== $userId) {
                return $this->fail('该手机号已被其它账号绑定');
            }

            $user->phone = $phone;
            $user->updated_at = time();
            $user->save();

            $this->logInfo('微信手机号绑定成功', ['user_id' => $userId]);
            return $this->ok([
                'phone' => $phone,
                'user' => $this->presentUser($user),
            ], '手机号绑定成功');
        } catch (Exception $e) {
            $this->logError('绑定手机号异常', ['user_id' => $userId, 'error' => $e->getMessage()]);
            return $this->fail('绑定失败，请稍后再试');
        }
    }

    // ------------------------------------------------------------------
    // 内部辅助方法
    // ------------------------------------------------------------------

    /**
     * 新版：通过 phone code 换取手机号
     * 文档：POST https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=ACCESS_TOKEN
     * @return array|null phone_info 节点
     */
    private function getPhoneByCode(string $code): ?array
    {
        $accessToken = $this->getMiniAccessToken();
        if (!$accessToken) {
            $this->logError('获取小程序 access_token 失败');
            return null;
        }

        $url = 'https://api.weixin.qq.com/wxa/business/getuserphonenumber?access_token=' . $accessToken;
        $resp = \app\utility\HttpHelper::curlPost($url, json_encode(['code' => $code], JSON_UNESCAPED_UNICODE));
        $data = is_string($resp) ? json_decode($resp, true) : null;

        if (is_array($data) && ($data['errcode'] ?? -1) === 0 && !empty($data['phone_info'])) {
            return $data['phone_info'];
        }
        $this->logError('getuserphonenumber 失败', ['resp' => $data]);
        return null;
    }

    /**
     * 获取并缓存小程序全局 access_token（有效期约 7200s，提前 300s 过期）
     */
    private function getMiniAccessToken(): string
    {
        $cacheKey = 'wx:mini:access_token';
        try {
            $cached = Redis::get($cacheKey);
            if ($cached) {
                return (string) $cached;
            }
        } catch (\Throwable $e) {
            // Redis 不可用则每次重新拉取
        }

        $config = config('wechat.miniprogram', []);
        $appId = $config['app_id'] ?? getenv('WECHAT_MINI_APPID') ?: '';
        $secret = $config['secret'] ?? getenv('WECHAT_MINI_SECRET') ?: '';
        if (empty($appId) || empty($secret)) {
            return '';
        }

        $url = 'https://api.weixin.qq.com/cgi-bin/token?'
            . http_build_query([
                'grant_type' => 'client_credential',
                'appid' => $appId,
                'secret' => $secret,
            ]);
        $resp = \app\utility\HttpHelper::curl($url);
        $data = is_string($resp) ? json_decode($resp, true) : null;

        if (is_array($data) && !empty($data['access_token'])) {
            $token = (string) $data['access_token'];
            $ttl = max(60, (int) ($data['expires_in'] ?? 7200) - 300);
            try {
                Redis::setex($cacheKey, $ttl, $token);
            } catch (\Throwable $e) {
                // 忽略缓存失败
            }
            return $token;
        }
        $this->logError('获取 access_token 失败', ['resp' => $data]);
        return '';
    }

    private function getCachedSessionKey(int $userId): string
    {
        try {
            return (string) Redis::get('user:wx_session_key:' . $userId);
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * 旧版：AES-128-CBC 解密微信加密数据
     */
    private function decryptWxData(string $sessionKey, string $iv, string $encryptedData): ?array
    {
        $aesKey = base64_decode($sessionKey);
        $aesIV = base64_decode($iv);
        $aesCipher = base64_decode($encryptedData);

        $decrypted = openssl_decrypt($aesCipher, 'AES-128-CBC', $aesKey, OPENSSL_RAW_DATA, $aesIV);
        if ($decrypted === false) {
            return null;
        }
        $data = json_decode($decrypted, true);
        return is_array($data) ? $data : null;
    }


    private function lookupUser(string $identifier, int $appId = 0): ?User
    {
        // 依次按 phone / email / nickname / openid 查找
        $repo = $this->repository;
        $user = $repo->findByPhone($identifier, $appId);
        if ($user) {
            return $user;
        }
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            $user = $repo->findByEmail($identifier, $appId);
            if ($user) {
                return $user;
            }
        }
        $user = $repo->findByNickname($identifier, $appId);
        if ($user) {
            return $user;
        }
        return $repo->findByOpenId($identifier, $appId);
    }

    /**
     * @return array{token:string, refresh_token:string, expires_in:int}
     */
    private function issueTokens(User $user): array
    {
        $accessTtl = (int) config('jwt.expire_time', 7200);
        $refreshTtl = (int) config('jwt.refresh_expire_time', 604800);

        $token = $this->jwt->generateToken([
            'user_id' => $user->id,
            'app_id' => $user->app_id ?? 0,
        ]);

        // 使用同一 secret 但携带 purpose=refresh 标记的二次签发
        $refreshToken = $this->jwt->generateToken([
            'user_id' => $user->id,
            'app_id' => $user->app_id ?? 0,
            'purpose' => 'refresh',
        ]);

        $this->trackRefreshToken($user->id, $refreshToken, $refreshTtl);

        return [
            'token' => $token,
            'refresh_token' => $refreshToken,
            'expires_in' => $accessTtl,
        ];
    }

    private function presentUser(User $user): array
    {
        return [
            'id' => (string) $user->id,
            'nickname' => $user->nickname,
            'avatar_url' => $user->avatar_url,
            'phone' => $user->phone,
            'level_id' => (string) $user->level_id,
            'app_id' => (string) $user->app_id,
            'referrer_id' => (string) ($user->referrer_id ?? 0),
            'is_dealer' => (int) ($user->is_dealer ?? 0),
        ];
    }

    /**
     * 调用微信 jscode2session
     * @return array 微信原样返回（含 openid/session_key 或 errcode/errmsg）
     */
    private function code2Session(string $code): array
    {
        $config = config('wechat.miniprogram', []);
        // 兼容 .env 直接写
        $appId = $config['app_id'] ?? getenv('WECHAT_MINI_APPID') ?: '';
        $secret = $config['secret'] ?? getenv('WECHAT_MINI_SECRET') ?: '';

        if (empty($appId) || empty($secret)) {
            $this->logError('微信小程序 appid/secret 未配置');
            return ['errcode' => -1, 'errmsg' => '小程序未配置 appid/secret'];
        }

        $url = 'https://api.weixin.qq.com/sns/jscode2session?'
            . http_build_query([
                'appid' => $appId,
                'secret' => $secret,
                'js_code' => $code,
                'grant_type' => 'authorization_code',
            ]);

        // 通过 HttpHelper 请求（生产建议配置 CURL_CA_BUNDLE；本地可临时 CURL_SSL_VERIFY=false）
        $resp = \app\utility\HttpHelper::curl($url);
        if ($resp === false) {
            return ['errcode' => -1, 'errmsg' => '请求微信服务器失败，请检查服务器 CA 证书或网络'];
        }
        if (!is_string($resp) || $resp === '') {
            return ['errcode' => -1, 'errmsg' => '微信服务器返回空响应'];
        }
        $data = json_decode($resp, true);
        return is_array($data) ? $data : ['errcode' => -1, 'errmsg' => '微信响应解析失败'];
    }

    /**
     * 缓存 session_key（用于后续 wx.getPhoneNumber 等敏感数据解密）
     */
    private function cacheSessionKey(int $userId, string $sessionKey): void
    {
        try {
            Redis::setex('user:wx_session_key:' . $userId, 1800, $sessionKey);
        } catch (\Throwable $e) {
            $this->logWarning('缓存 wx session_key 失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    /**
     * 解析并验证推荐人ID
     *
     * @param mixed $ref  推荐人用户ID（int 或 numeric string）
     * @param int   $appId
     * @return int  验证通过的推荐人ID，无效时返回 0
     */
    private function resolveReferrerId($ref, int $appId = 0): int
    {
        $referrerId = (int) $ref;
        if ($referrerId <= 0) {
            return 0;
        }

        // 验证推荐人存在且未禁用
        $referrer = $this->repository->find($referrerId);
        if (!$referrer) {
            return 0;
        }
        if ((int) ($referrer->deleted_at ?? 0) === 1) {
            return 0;
        }

        // 不能自推荐
        return $referrerId;
    }

    /**
     * 写入推荐关系记录到 yxshop_dealer_referees
     * 已存在则不重复写入
     */
    private function createRefereeRecord(int $referrerId, int $userId, int $appId): void
    {
        // 推荐关系（商业版分销功能，开源版跳过）
        if (!class_exists('\\commercial\\distribution\\Model\\DealerReferee')) {
            return;
        }

        try {
            $refereeRepo = new \commercial\distribution\Repository\DealerRefereeRepository();
            $exists = $refereeRepo->query()
                ->where('dealer_id', $referrerId)
                ->where('referee_id', $userId)
                ->exists();
            if ($exists) {
                return;
            }

            $referrer = $this->repository->find($referrerId);
            $refereeRepo->create([
                'dealer_id'         => $referrerId,
                'referee_id'        => $userId,
                'referee_type'      => \commercial\distribution\Model\DealerReferee::TYPE_USER,
                'referee_name'      => $referrer->nickname ?? '',
                'referee_phone'     => $referrer->phone ?? '',
                'referee_commission' => 0,
                'referee_status'    => \commercial\distribution\Model\DealerReferee::STATUS_APPROVED,
                'referee_time'      => time(),
                'app_id'            => $appId,
                'created_at'        => time(),
                'updated_at'        => time(),
            ]);

            $this->logInfo('推荐关系已绑定', [
                'referrer_id' => $referrerId,
                'user_id'     => $userId,
            ]);
        } catch (\Throwable $e) {
            // 推荐关系记录写入失败不影响注册
            $this->logWarning('推荐关系记录写入失败', [
                'referrer_id' => $referrerId,
                'user_id'     => $userId,
                'error'       => $e->getMessage(),
            ]);
        }
    }

    // ok() / fail() 由 BaseService 提供，不再本地覆盖

    // -- Redis 辅助：reset token / refresh token / blacklist --

    private function storeResetToken(string $token, int $userId, int $ttl): void
    {
        try {
            Redis::setex(self::RESET_TOKEN_PREFIX . $token, $ttl, $userId);
        } catch (\Throwable $e) {
            // Redis 不可用时降级到 DB session（这里简化为忽略）
            $this->logError('存储重置 token 失败', ['error' => $e->getMessage()]);
        }
    }

    private function consumeResetToken(string $token): int
    {
        try {
            $key = self::RESET_TOKEN_PREFIX . $token;
            $userId = (int) Redis::get($key);
            if ($userId > 0) {
                Redis::del($key);
            }
            return $userId;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function trackRefreshToken(int $userId, string $refreshToken, int $ttl): void
    {
        try {
            $key = self::REFRESH_TOKEN_PREFIX . $userId;
            Redis::sAdd($key, hash('sha256', $refreshToken));
            Redis::expire($key, $ttl);
        } catch (\Throwable $e) {
            // 软失败：仅记录，不阻塞登录
        }
    }

    private function isRefreshTokenActive(int $userId, string $refreshToken): bool
    {
        try {
            $key = self::REFRESH_TOKEN_PREFIX . $userId;
            return (bool) Redis::sIsMember($key, hash('sha256', $refreshToken));
        } catch (\Throwable $e) {
            // 若 Redis 不可用，退化为放行（避免影响业务），但记录告警
            $this->logWarning('refresh token 白名单 Redis 不可用', ['user_id' => $userId]);
            return true;
        }
    }

    private function revokeRefreshTokens(int $userId): void
    {
        try {
            Redis::del(self::REFRESH_TOKEN_PREFIX . $userId);
        } catch (\Throwable $e) {
            // 忽略
        }
    }

    private function blacklistToken(string $token, int $ttl): void
    {
        try {
            Redis::setex(self::TOKEN_BLACKLIST_PREFIX . hash('sha256', $token), $ttl, 1);
        } catch (\Throwable $e) {
            // 忽略
        }
    }
}
