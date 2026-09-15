<?php

namespace app\service;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtService
{
    private $secret;
    private $algorithm;
    private $expireTime;
    
    /**
     * 已知的弱密钥/默认密钥，禁止在生产环境使用
     */
    private const WEAK_SECRETS = [
        '',
        'your-secret-key',
        'your_jwt_secret_here',
        'changeme_yxshop_jwt_secret_2024',
        'secret',
        'jwt_secret',
    ];

    public function __construct()
    {
        $this->secret = config('jwt.secret', '');
        $this->algorithm = config('jwt.algorithm', 'HS256');
        $this->expireTime = config('jwt.expire_time', 7200); // 2小时

        // 生产环境强制校验密钥强度
        $this->validateSecret();
    }

    /**
     * 校验 JWT 密钥强度
     * 生产环境使用弱密钥时抛出异常，阻止启动
     */
    private function validateSecret(): void
    {
        $mode = env('APP_MODE', 'development');

        if ($mode === 'production' || $mode === 'prod') {
            if (in_array($this->secret, self::WEAK_SECRETS, true)) {
                throw new \RuntimeException(
                    '生产环境必须配置强 JWT_SECRET（至少32位随机字符串）。'
                    . '请通过 .env 文件设置 JWT_SECRET，'
                    . '生成方式: php -r "echo bin2hex(random_bytes(32));"'
                );
            }
            if (strlen($this->secret) < 32) {
                throw new \RuntimeException(
                    '生产环境 JWT_SECRET 长度不足，至少需要 32 位字符。'
                    . '生成方式: php -r "echo bin2hex(random_bytes(32));"'
                );
            }
        }

        // 开发环境如果密钥为空，使用随机生成的临时密钥并记录警告
        if (in_array($this->secret, self::WEAK_SECRETS, true)) {
            $this->secret = bin2hex(random_bytes(32));
            \support\Log::warning('JwtService: JWT_SECRET 未配置，已生成临时密钥。生产环境务必在 .env 中配置固定密钥。');
        }
    }
    
    /**
     * 生成JWT token
     */
    public function generateToken(array $payload, ?int $expireTime = null): string
    {
        $now = time();
        $ttl = $expireTime ?? $this->expireTime;
        
        $token = [
            'iat' => $now,
            'exp' => $now + $ttl,
            'nbf' => $now,
            'iss' => 'yxadmin',
            'aud' => 'merchant',
            'data' => $payload
        ];
        
        return JWT::encode($token, $this->secret, $this->algorithm);
    }

    public function generateTempToken(array $payload, string $scope = 'password_change', int $expireTime = 1800): string
    {
        $payload['scope'] = $scope;
        return $this->generateToken($payload, $expireTime);
    }
    
    /**
     * 验证JWT token
     */
    public function validateToken(string $token): array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, $this->algorithm));
            
            // 将对象转换为数组
            $decodedArray = json_decode(json_encode($decoded), true);
            
            return [
                'success' => true,
                'data' => $decodedArray['data'],
                'exp' => $decodedArray['exp']
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Token验证失败：' . $e->getMessage()
            ];
        }
    }
    
    /**
     * 刷新JWT token
     */
    public function refreshToken(string $token): array
    {
        $validation = $this->validateToken($token);
        
        if (!$validation['success']) {
            return $validation;
        }
        
        // 生成新的token
        $newToken = $this->generateToken($validation['data']);
        
        return [
            'success' => true,
            'token' => $newToken,
            'message' => 'Token刷新成功'
        ];
    }
    
    /**
     * 从token中获取用户信息
     */
    public function getUserFromToken(string $token): array
    {
        $validation = $this->validateToken($token);
        
        if (!$validation['success']) {
            return $validation;
        }
        
        return [
            'success' => true,
            'user' => $validation['data']
        ];
    }
    
    /**
     * 检查token是否即将过期
     */
    public function isTokenExpiringSoon(string $token): bool
    {
        $validation = $this->validateToken($token);

        if (!$validation['success']) {
            return false;
        }

        $exp = $validation['exp'];
        $now = time();

        return ($exp - $now) < 1800;
    }

    public function blacklistToken(string $token): void
    {
        $validation = $this->validateToken($token);
        if ($validation['success']) {
            $ttl = ($validation['exp'] ?? time()) - time();
            if ($ttl > 0) {
                \support\Redis::setex("jwt_blacklist:" . md5($token), $ttl, '1');
            }
        }
    }

    public function isBlacklisted(string $token): bool
    {
        return (bool) \support\Redis::get("jwt_blacklist:" . md5($token));
    }
} 