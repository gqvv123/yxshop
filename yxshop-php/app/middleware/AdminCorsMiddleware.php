<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Request;
use Webman\Http\Response;

class AdminCorsMiddleware implements MiddlewareInterface
{
    private array $allowedOrigins;

    public function __construct()
    {
        $this->allowedOrigins = config('cors.admin_allowed_origins', ['http://localhost:5173']);
    }

    public function process(Request $request, callable $handler): Response
    {
        $origin = $request->header('origin', '');

        if ($request->method() === 'OPTIONS') {
            $response = new Response(204);
        } else {
            $response = $handler($request);
        }

        if ($origin && in_array($origin, $this->allowedOrigins, true)) {
            $response->withHeaders([
                'Access-Control-Allow-Origin' => $origin,
                'Access-Control-Allow-Methods' => 'GET, POST, PUT, DELETE, PATCH, OPTIONS',
                'Access-Control-Allow-Headers' => 'Content-Type, Authorization, Accept-Language, X-Requested-With',
                'Access-Control-Allow-Credentials' => 'true',
                'Access-Control-Max-Age' => '86400',
            ]);
        }

        return $response;
    }
}