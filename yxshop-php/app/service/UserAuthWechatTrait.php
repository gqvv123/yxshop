<?php

namespace app\service;
use app\model\User;
use support\Redis;
use Exception;

/**
 * UserAuthWechatTrait：从 UserAuthService.php 抽离的wxLogin、updateWxProfile、bindWxPhone、getPhoneByCode、getMiniAccessToken、getCachedSessionKey、decryptWxData、code2Session、cacheSessionKey等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait UserAuthWechatTrait
{
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
}
