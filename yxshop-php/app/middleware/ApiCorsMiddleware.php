<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use Webman\MiddlewareInterface;

class ApiCorsMiddleware implements MiddlewareInterface
{
    /**
     * 处理请求
     */
    public function process(Request $request, callable $handler): Response
    {
        // 处理预检请求
        if ($request->method() === 'OPTIONS') {
            return $this->handlePreflightRequest();
        }

        $response = $handler($request);

        // 添加CORS头
        $this->addCorsHeaders($response);

        return $response;
    }

    /**
     * 处理预检请求
     */
    private function handlePreflightRequest(): Response
    {
        $response = response('', 200);
        $this->addCorsHeaders($response);
        return $response;
    }

    /**
     * 添加CORS头
     */
    private function addCorsHeaders(Response $response): void
    {
        $origin = request()->header('origin', '');
        $allowedOrigins = config('app.cors_origins', []);
        if (!empty($allowedOrigins) && is_array($allowedOrigins) && in_array($origin, $allowedOrigins, true)) {
            $response->header('Access-Control-Allow-Origin', $origin);
            $response->header('Access-Control-Allow-Credentials', 'true');
        }
        $response->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, X-Version');
        $response->header('Access-Control-Max-Age', '86400');
    }
}