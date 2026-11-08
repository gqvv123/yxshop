<?php

/**
 * 全局辅助函数：misc 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('convertArrayToObject')) {
    /**
     * 数组转对象
     */
    function convertArrayToObject(array $array): object
    {
        return (object)$array;
    }
}

if (!function_exists('getSeller')) {
    /**
     * 获取所有商家
     */
    function getSeller()
    {
        return \app\model\Seller::get();
    }
}

if (!function_exists('update_env')) {
    /**
     * 更新 .env 文件配置
     */
    function update_env(string $key, string $newValue): void
    {
        $path = base_path('.env');
        $envContent = file_get_contents($path);
        if (preg_match('/^' . preg_quote($key, '/') . '=/m', $envContent)) {
            $envContent = preg_replace('/^' . preg_quote($key, '/') . '.*/m', $key . '=' . $newValue, $envContent);
        } else {
            $envContent .= PHP_EOL . $key . '=' . $newValue . PHP_EOL;
        }
        file_put_contents($path, $envContent);
    }
}

if (!function_exists('route_is')) {
    /**
     * 检查当前路由是否匹配指定模式
     */
    function route_is(string ...$patterns): bool
    {
        if (!function_exists('request')) {
            return false;
        }
        $request = request();
        if (!$request || !method_exists($request, 'routeIs')) {
            return false;
        }
        return $request->routeIs(...$patterns);
    }
}

if (!function_exists('current_route_name')) {
    /**
     * 获取当前路由名称
     */
    function current_route_name(): string
    {
        $request = function_exists('request') ? request() : null;
        if ($request && isset($request->route) && $request->route instanceof \Webman\Route\Route) {
            return $request->route->getName() ?? '';
        }
        return '';
    }
}

if (!function_exists('current_route_url')) {
    /**
     * 获取当前路由URL
     */
    function current_route_url(?array $parameters = null): string
    {
        $request = function_exists('request') ? request() : null;
        if ($request && isset($request->route) && $request->route instanceof \Webman\Route\Route) {
            $route = $request->route;
            $name = $route->getName();
            $routeParams = $route->param();
            if (!is_array($routeParams)) {
                $routeParams = [];
            }
            if ($parameters !== null) {
                $routeParams = array_merge($routeParams, $parameters);
            }
            if ($name) {
                return route($name, $routeParams);
            }
            $path = '/' . ltrim($request->path(), '/');
            if (!empty($routeParams)) {
                return $path . '?' . http_build_query($routeParams);
            }
            return $path;
        }
        $uri = $_SERVER['REQUEST_URI'] ?? '/';
        if ($parameters !== null && $parameters !== []) {
            $uri = strtok($uri, '?');
            return $uri . '?' . http_build_query($parameters);
        }
        return $uri ?: '/';
    }
}

if (!function_exists('generatePrefixedHash')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::generatePrefixedHash() 替代
     */
    function generatePrefixedHash(string $prefix = 'PL_'): string
    {
        return \app\utility\StringHelper::generatePrefixedHash($prefix);
    }
}
