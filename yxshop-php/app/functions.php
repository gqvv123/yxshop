<?php
/**
 * Yxwebai-admin 全局辅助函数
 *
 * 结构说明：
 * - 第一部分：Webman 框架核心函数（env, config, json, asset 等）
 * - 第二部分：业务核心函数（密码、翻译、设置、权限等）
 * - 第三部分：deprecated 转发函数（转发到 app\utility\* 类）
 */

// ============================================================================
// 第一部分：Webman 框架核心函数
// ============================================================================

// ============================================================================
// 第二部分：业务核心函数
// ============================================================================

if (!class_exists('Route')) {
    /**
     * Route 辅助类（兼容 Blade 模板中的 Route::currentRouteName() 等调用）
     */
    class Route
    {
        public static function currentRouteName(): string
        {
            return current_route_name();
        }

        public static function current()
        {
            $request = function_exists('request') ? request() : null;
            if ($request && isset($request->route) && $request->route instanceof \Webman\Route\Route) {
                return new class($request->route) {
                    private $route;
                    public function __construct($route)
                    {
                        $this->route = $route;
                    }
                    public function getName(): ?string
                    {
                        return $this->route->getName();
                    }
                    public function parameters(): array
                    {
                        $params = $this->route->param();
                        return is_array($params) ? $params : [];
                    }
                    public function parameter(string $name, $default = null)
                    {
                        return $this->route->param($name, $default);
                    }
                    public function __call(string $name, array $arguments)
                    {
                        if (method_exists($this->route, $name)) {
                            return $this->route->$name(...$arguments);
                        }
                        throw new \BadMethodCallException("Method {$name} does not exist on current route");
                    }
                };
            }
            return null;
        }
    }
}

/**
 * 递归生成树状菜单结构
 */
function build_menu_tree(array &$elements, $parentId = 0): array
{
    $branch = [];
    foreach ($elements as $element) {
        if ((int)$element['parent_id'] === (int)$parentId) {
            $node = [
                'name'     => $element['name'],
                'model'    => $element['model'] ?? null,
                'icon'     => $element['icon'],
                'url'      => $element['url'] ?? null,
                'children' => [],
            ];
            $children = build_menu_tree($elements, $element['id']);
            if ($children) {
                $node['children'] = $children;
                if (empty($node['url'])) {
                    $node['url'] = null;
                }
            }
            $branch[] = $node;
        }
    }
    return $branch;
}

// ============================================================================
// 第三部分：deprecated 转发函数（转发到 app\utility\* 类）
// ============================================================================

// ---- StringHelper 转发 ----

if (!function_exists('k2t')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::k2t() 替代
     */
    function k2t(string $text): string
    {
        return \app\utility\StringHelper::k2t($text);
    }
}

if (!function_exists('t2k')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::t2k() 替代
     */
    function t2k(string $text, ?string $replace = '_'): string
    {
        return \app\utility\StringHelper::t2k($text, $replace);
    }
}

// ---- MoneyHelper 转发 ----

// ---- OrderHelper 转发 ----

// ---- FileHelper 转发 ----

// ---- HttpHelper 转发 ----

// ---- DateHelper 转发 ----

/*
 * 全局辅助函数入口。
 *
 * 为控制单文件体积，这里按主题把全局函数拆到 app/helpers/ 下：
 *   base_helper   —— 基础运行时（env/config/json/asset/url/route/request 等）
 *   auth_helper   —— 认证授权（密码、登录态、CSRF、权限）
 *   i18n_helper   —— 多语言与站点配置
 *   misc_helper   —— 其它零散工具函数
 *
 * 本文件由 composer 的 autoload.files 加载，随后 require 各个 helper。
 */

require_once __DIR__ . '/helpers/base_helper.php';
require_once __DIR__ . '/helpers/auth_helper.php';
require_once __DIR__ . '/helpers/i18n_helper.php';
require_once __DIR__ . '/helpers/misc_helper.php';
require_once __DIR__ . '/helpers/string_helper.php';
require_once __DIR__ . '/helpers/money_helper.php';
require_once __DIR__ . '/helpers/status_helper.php';
require_once __DIR__ . '/helpers/file_helper.php';
require_once __DIR__ . '/helpers/http_helper.php';
require_once __DIR__ . '/helpers/date_helper.php';
require_once __DIR__ . '/helpers/api_helper.php';
