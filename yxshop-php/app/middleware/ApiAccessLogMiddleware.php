<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use app\model\SystemLog;
use Webman\MiddlewareInterface;

class ApiAccessLogMiddleware implements MiddlewareInterface
{
    protected array $excludePaths = [
        '/admin/api/health',
        '/admin/api/captcha',
    ];

    public function process(Request $request, callable $handler): Response
    {
        $path = $request->path();
        if (in_array($path, $this->excludePaths)) {
            return $handler($request);
        }

        $startTime = microtime(true);

        $response = $handler($request);

        $executionTime = round((microtime(true) - $startTime) * 1000, 2);
        $params = $request->all();
        $paramsSummary = substr(json_encode($params, JSON_UNESCAPED_UNICODE), 0, 500);

        try {
            $statusCode = $response->getStatusCode();
        } catch (\Throwable $e) {
            $statusCode = 500;
        }

        try {
            SystemLog::record(
                SystemLog::LEVEL_INFO,
                'API Access: ' . $request->method() . ' ' . $path,
                null,
                '',
                0,
                '',
                'api_access',
                '',
                $request->method(),
                $request->getRealIp(),
                $request->header('User-Agent', ''),
                0
            );
        } catch (\Throwable $e) {
            error_log('[ApiAccessLogMiddleware] log failed: ' . $e->getMessage());
        }

        return $response;
    }
}