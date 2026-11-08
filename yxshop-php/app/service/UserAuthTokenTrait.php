<?php

namespace app\service;
use app\model\User;
use support\Redis;
use Exception;

/**
 * UserAuthTokenTrait：从 UserAuthService.php 抽离的refreshToken、forgotPassword、resetPassword、issueTokens、presentUser、storeResetToken、consumeResetToken、trackRefreshToken、isRefreshTokenActive、revokeRefreshTokens、blacklistToken等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait UserAuthTokenTrait
{
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
