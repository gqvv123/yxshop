<?php

namespace app\service;

use app\model\User;
use app\repository\UserRepository;
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
    use UserAuthTokenTrait;

    use UserAuthWechatTrait;

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
                    'username' => $username,
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

    // ------------------------------------------------------------------
    // 内部辅助方法
    // ------------------------------------------------------------------

    private function lookupUser(string $identifier, int $appId = 0): ?User
    {
        // 依次按 username / phone / email / nickname / openid 查找
        $repo = $this->repository;
        $user = $repo->findByUsername($identifier, $appId);
        if ($user) {
            return $user;
        }
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

}
