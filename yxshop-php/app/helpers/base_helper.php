<?php

/**
 * 全局辅助函数：base 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('env')) {
    /**
     * 从 YAML 配置文件获取环境变量
     */
    function env($key, $default = null)
    {
        return \app\helpers\ConfigLoader::get($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * 从 YAML 配置文件获取配置值
     */
    function config($key, $default = null)
    {
        return \app\helpers\ConfigLoader::get($key, $default);
    }
}

if (!function_exists('json')) {
    /**
     * 返回JSON响应
     */
    function json($data, $status = 200)
    {
        $response = new \support\Response();
        $response->withHeader('Content-Type', 'application/json');
        $response->withStatus($status);
        $response->withBody(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        return $response;
    }
}

if (!function_exists('asset')) {
    /**
     * 生成静态资源URL
     */
    function asset(string $path = ''): string
    {
        $baseUrl = env('APP_URL', 'http://localhost:8777');
        return rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('base_url')) {
    /**
     * 获取当前域名及根路径
     */
    function base_url(): string
    {
        if (function_exists('request') && class_exists('Webman\Http\Request')) {
            try {
                $request = request();
                $host = $request->host();
                $path = $request->path();
                $protocol = 'http';
                if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                    $protocol = 'https';
                } elseif ($request->header('x-forwarded-proto') === 'https') {
                    $protocol = 'https';
                }
                $baseUrl = $protocol . '://' . $host;
                if (str_starts_with($path, '/admin')) {
                    $baseUrl .= '/admin';
                }
                return $baseUrl . '/';
            } catch (\Exception $e) {
            // request() 在 CLI 场景不可用时，回退到 $_SERVER 推断基址
        }
        }
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost:8777';
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
        $subDir = str_replace('\\', '/', dirname($scriptName));
        return $protocol . '://' . $host . $subDir . ($subDir === '/' ? '' : '/');
    }
}

if (!function_exists('url')) {
    /**
     * 生成URL路径
     */
    function url($path = ''): string
    {
        if (empty($path)) {
            return base_url();
        }
        if (str_starts_with($path, '/')) {
            return $path;
        }
        return base_url() . ltrim($path, '/');
    }
}

if (!function_exists('route')) {
    /**
     * 生成路由URL
     */
    function route(string $name, $parameters = []): string
    {
        $routes = [
            'admin.login' => '/admin/login',
            'admin.authenticate' => '/admin/authenticate',
            'admin.logout' => '/admin/logout',
            'admin.dashboard' => '/admin',
            'admin.password.request' => '/admin/password/request',
            'admin.profile' => '/admin/profile',
            'home' => '/',
            'default.image' => '/assets/images/default.jpg',
        ];
        $url = $routes[$name] ?? '/';
        if (!empty($parameters)) {
            if (is_array($parameters)) {
                $url .= '?' . http_build_query($parameters);
            } else {
                $url .= '/' . $parameters;
            }
        }
        $baseUrl = env('APP_URL', 'http://localhost:8777');
        return rtrim($baseUrl, '/') . $url;
    }
}

if (!function_exists('app')) {
    /**
     * 获取应用实例
     */
    function app($abstract = null)
    {
        if (is_null($abstract)) {
            return new class {
                public function getLocale(): string
                {
                    return config('app.locale', 'zh-CN');
                }
                public function make($abstract)
                {
                    return null;
                }
            };
        }
        return null;
    }
}

if (!function_exists('request')) {
    /**
     * 创建 Request 辅助对象以支持 routeIs() 方法
     */
    function request()
    {
        static $requestHelper = null;
        if ($requestHelper === null) {
            $requestHelper = new class {
                public function routeIs($pattern): bool
                {
                    $currentPath = $_SERVER['REQUEST_URI'] ?? '/';
                    $currentPath = strtok($currentPath, '?');
                    if (str_contains($pattern, '*')) {
                        $regex = str_replace('*', '.*', $pattern);
                        $regex = str_replace('.', '\.', $regex);
                        $regex = str_replace('/', '\/', $regex);
                        return preg_match('/^' . $regex . '$/', $currentPath) === 1;
                    }
                    $routes = [
                        'admin.dashboard' => '/admin',
                        'admin.login' => '/admin/login',
                        'admin.logout' => '/admin/logout',
                        'admin.index' => '/admin/admin',
                        'admin.edit' => '/admin/admin/*/edit',
                        'admin.create' => '/admin/admin/create',
                        'admin.role.index' => '/admin/role',
                        'admin.role.*' => '/admin/role*',
                    ];
                    $routePath = $routes[$pattern] ?? $pattern;
                    if (str_contains($routePath, '*')) {
                        $regex = str_replace('*', '.*', $routePath);
                        $regex = str_replace('/', '\/', $regex);
                        return preg_match('/^' . $regex . '/', $currentPath) === 1;
                    }
                    return $currentPath === $routePath || str_starts_with($currentPath, $routePath . '/');
                }
                public function path(): string
                {
                    return $_SERVER['REQUEST_URI'] ?? '/';
                }
                public function url(): string
                {
                    return $_SERVER['REQUEST_URI'] ?? '/';
                }
            };
        }
        return $requestHelper;
    }
}

if (!function_exists('rpc')) {
    /**
     * 内部RPC调用
     */
    function rpc($class, $method, $args = [])
    {
        $client = stream_socket_client('tcp://127.0.0.1:9512', $errorCode, $errorMessage);
        if (false === $client) {
            throw new \Exception('rpc failed to connect: ' . $errorMessage);
        }
        $request = [
            'class'  => $class,
            'method' => $method,
            'args'   => $args,
        ];
        $jsonData = json_encode($request) . "\n";
        fwrite($client, $jsonData);
        $result = fgets($client, 10240000);
        return json_decode($result, true);
    }
}
