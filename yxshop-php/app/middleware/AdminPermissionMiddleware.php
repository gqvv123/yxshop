<?php

namespace app\middleware;

use Webman\MiddlewareInterface;
use Webman\Http\Response;
use Webman\Http\Request;
use app\service\PermissionService;

class AdminPermissionMiddleware implements MiddlewareInterface
{
    protected $permissionService;
    
    public function __construct()
    {
        $this->permissionService = new PermissionService();
    }
    
    public function process(Request $request, callable $handler): Response
    {
        $user = $request->admin;
        if (!$user) {
            return json([
                'code' => 401,
                'message' => '用户未认证',
                'timestamp' => date('Y-m-d H:i:s')
            ], 401);
        }
        
        // 获取当前请求的路径和方法
        $path = $request->path();
        $method = strtoupper($request->method());
        
        // 构建权限标识
        $permission = strtolower($method) . ':' . $path;
        
        $appId = $user->app_id ?? 0;
        
        $hasPermission = $this->permissionService->checkUserPermission($appId, $user->id, $permission);
        
        if (!$hasPermission) {
            return json([
                'code' => 403,
                'message' => '权限不足',
                'timestamp' => date('Y-m-d H:i:s')
            ], 403);
        }
        
        return $handler($request);
    }
} 