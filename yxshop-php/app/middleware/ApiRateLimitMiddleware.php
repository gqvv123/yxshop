<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use support\Redis;
use Webman\MiddlewareInterface;

class ApiRateLimitMiddleware implements MiddlewareInterface
{
    /**
     * 处理请求
     */
    public function process(Request $request, callable $handler): Response
    {
        $key = $this->getRateLimitKey($request);
        $limit = $this->getRateLimit($request);
        $window = $this->getRateLimitWindow($request);

        if (!$this->checkRateLimit($key, $limit, $window)) {
            return $this->rateLimitExceeded();
        }

        return $handler($request);
    }

    /**
     * 获取限流键
     */
    private function getRateLimitKey(Request $request): string
    {
        $ip = $request->getRealIp();
        $route = $request->path();
        return "rate_limit:{$ip}:{$route}";
    }

    /**
     * 获取限流次数
     */
    private function getRateLimit(Request $request): int
    {
        // 根据路由设置不同的限流
        $route = $request->path();
        
        if (strpos($route, '/api/v1/auth/') === 0) {
            return 5; // 认证接口限制更严格
        }
        
        if (strpos($route, '/api/v1/upload/') === 0) {
            return 10; // 上传接口限制
        }
        
        return 100; // 默认限制
    }

    /**
     * 获取限流时间窗口（秒）
     */
    private function getRateLimitWindow(Request $request): int
    {
        return 60; // 1分钟
    }

    /**
     * 检查限流
     */
    private function checkRateLimit(string $key, int $limit, int $window): bool
    {
        try {
            $current = Redis::incr($key);

            if ($current === 1) {
                Redis::expire($key, $window);
            }

            return $current <= $limit;
        } catch (\Throwable $e) {
            // Redis 不可用时降级放行，避免整站 500；生产环境应接入监控告警
            \support\Log::warning('API限流Redis不可用，已降级放行', [
                'key' => $key,
                'error' => $e->getMessage(),
            ]);
            return true;
        }
    }

    /**
     * 限流超出响应
     */
    private function rateLimitExceeded(): Response
    {
        return json([
            'code' => 429,
            'message' => '请求过于频繁，请稍后再试',
            'data' => null
        ], 429);
    }
}