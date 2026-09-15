<?php

namespace app\middleware;

use Webman\Http\Request;
use Webman\Http\Response;
use support\Db;
use Webman\MiddlewareInterface;

class OperationLogMiddleware implements MiddlewareInterface
{
    protected array $excludePaths = [
        '/admin/api/health',
        '/admin/api/captcha',
    ];

    protected array $excludePatterns = [
        '~/admin/api/upload/~',
    ];

    public function process(Request $request, callable $handler): Response
    {
        $method = $request->method();
        if (in_array($method, ['GET', 'OPTIONS', 'HEAD'])) {
            return $handler($request);
        }

        $path = $request->path();
        if (in_array($path, $this->excludePaths)) {
            return $handler($request);
        }

        foreach ($this->excludePatterns as $pattern) {
            if (preg_match($pattern, $path)) {
                return $handler($request);
            }
        }

        $startTime = microtime(true);

        try {
            $response = $handler($request);
            $this->logOperation($request, $response, $startTime, 'success');
            return $response;
        } catch (\Exception $e) {
            $this->logOperation($request, null, $startTime, 'failed', $e->getMessage());
            throw $e;
        }
    }

    private function logOperation(Request $request, $response, float $startTime, string $status, string $errorMessage = ''): void
    {
        try {
            $user = $this->getCurrentUser($request);
            if (!$user) {
                return;
            }

            $executionTime = round((microtime(true) - $startTime) * 1000);
            $path = $request->path();
            $method = $request->method();
            $ip = $request->getRealIp();
            $userAgent = $request->header('User-Agent');
            $module = $this->parseModule($path);
            $action = $this->parseAction($method, $path);
            $requestData = $this->filterSensitiveData($request->all());

            $responseData = null;
            if ($response && $status === 'success') {
                $responseData = $this->getResponseData($response);
            }

            Db::table('yxshop_admin_logs')->insert([
                'admin_id' => $user['id'] ?? 0,
                'admin_name' => $user['username'] ?? '',
                'module' => $module,
                'action' => $action,
                'operation_type' => $this->getOperationType($action),
                'target_type' => $module,
                'target_id' => 0,
                'target_name' => '',
                'request_url' => $path,
                'request_method' => $method,
                'request_params' => json_encode($requestData),
                'response_data' => $responseData ? json_encode($responseData) : null,
                'operation_result' => $status === 'success' ? 10 : 20,
                'error_message' => $errorMessage,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'app_id' => $user['app_id'] ?? 0,
                'created_at' => time()
            ]);
        } catch (\Exception $e) {
        }
    }

    private function getCurrentUser(Request $request): ?array
    {
        // JWT AdminAuthMiddleware sets $request->admin (Admin model object)
        $admin = $request->admin ?? null;
        if ($admin) {
            if (is_object($admin)) {
                return [
                    'id' => $admin->id ?? 0,
                    'username' => $admin->username ?? $admin->nickname ?? '',
                    'app_id' => $admin->app_id ?? 0,
                ];
            }
            if (is_array($admin)) {
                return [
                    'id' => $admin['id'] ?? $admin['admin_id'] ?? 0,
                    'username' => $admin['username'] ?? '',
                    'app_id' => $admin['app_id'] ?? 0,
                ];
            }
        }

        // Fallback: session-based auth
        $session = $request->session();
        $sessionAdmin = $session ? $session->get('admin') : null;
        if ($sessionAdmin) {
            return [
                'id' => $sessionAdmin['id'] ?? $sessionAdmin['admin_id'] ?? 0,
                'username' => $sessionAdmin['username'] ?? '',
                'app_id' => $sessionAdmin['app_id'] ?? 0,
            ];
        }

        return null;
    }

    private function parseModule(string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        // path format: admin/api/{module}/...
        if (count($segments) >= 3 && $segments[0] === 'admin' && $segments[1] === 'api') {
            return $segments[2] ?? 'unknown';
        }
        if (count($segments) >= 2 && $segments[0] === 'api') {
            return $segments[1] ?? 'unknown';
        }
        return $segments[0] ?? 'unknown';
    }

    private function parseAction(string $method, string $path): string
    {
        $segments = explode('/', trim($path, '/'));
        // path format: admin/api/{module}[/{id}]
        // id position is index 3 (admin=0, api=1, module=2, id=3)
        $idIndex = 3;
        if (count($segments) >= 2 && $segments[0] === 'api') {
            // path format: api/{module}[/{id}]
            $idIndex = 2;
        }
        switch ($method) {
            case 'GET':
                if (count($segments) > $idIndex && is_numeric($segments[$idIndex])) {
                    return 'show';
                }
                return 'list';
            case 'POST':
                return 'create';
            case 'PUT':
            case 'PATCH':
                return 'update';
            case 'DELETE':
                return 'delete';
            default:
                return 'unknown';
        }
    }

    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = ['password', 'token', 'secret', 'key', 'auth', 'api_key', 'access_token'];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '***';
            }
        }

        return $data;
    }

    private function getResponseData(Response $response)
    {
        try {
            $content = $response->rawBody();
            if (is_string($content)) {
                $data = json_decode($content, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $data;
                }
            }
        } catch (\Exception $e) {
        }
        return null;
    }

    private function getOperationType(string $action): int
    {
        return match ($action) {
            'list', 'show' => 10,
            'create' => 20,
            'update' => 30,
            'delete' => 40,
            'export' => 50,
            'import' => 60,
            default => 10,
        };
    }

    private function generateDescription(string $module, string $action, string $path): string
    {
        $moduleNames = [
            'orders' => '订单',
            'order' => '订单',
            'items' => '商品',
            'item' => '商品',
            'users' => '用户',
            'user' => '用户',
            'categories' => '分类',
            'category' => '分类',
            'coupons' => '优惠券',
            'coupon' => '优惠券',
            'settings' => '设置',
            'setting' => '设置',
            'admin' => '管理员',
            'role' => '角色',
            'finance' => '财务',
            'report' => '报表',
            'marketing' => '营销',
        ];

        $actionNames = [
            'list' => '查看列表',
            'show' => '查看详情',
            'create' => '创建',
            'update' => '更新',
            'delete' => '删除',
        ];

        $moduleName = $moduleNames[$module] ?? $module;
        $actionName = $actionNames[$action] ?? $action;
        return $actionName . $moduleName;
    }
}
