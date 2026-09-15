<?php
/**
 * Yxwebai-admin 全局辅助函数
 *
 * 结构说明：
 * - 第一部分：Webman 框架核心函数（env, config, json, asset 等）
 * - 第二部分：业务核心函数（密码、翻译、设置、权限等）
 * - 第三部分：deprecated 转发函数（转发到 app\utility\* 类）
 */

use app\common\StatusEnum;
use app\model\Order;
use app\model\Setting;
use app\repository\SettingRepository;
use app\service\cache\TieredCache;
use app\service\SettingService;
use Illuminate\Support\Arr;


// ============================================================================
// 第一部分：Webman 框架核心函数
// ============================================================================

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

// ============================================================================
// 第二部分：业务核心函数
// ============================================================================

if (!function_exists('yxmall_pass')) {
    /**
     * 生成密码hash值
     */
    function yxmall_pass($password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('yxmall_pass_verify')) {
    /**
     * 验证密码（兼容旧版 md5 双重加密）
     */
    function yxmall_pass_verify($password, $hash): bool
    {
        if (password_verify($password, $hash)) {
            return true;
        }
        return md5(md5($password) . 'yxmall_ok') === $hash;
    }
}

if (!function_exists('yxmall_pass_needs_rehash')) {
    /**
     * 检查密码是否需要重新hash
     */
    function yxmall_pass_needs_rehash($hash): bool
    {
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$2b$')) {
            return password_needs_rehash($hash, PASSWORD_DEFAULT);
        }
        return true;
    }
}

if (!function_exists('translate')) {
    /**
     * 翻译静态文本
     */
    function translate($keyWord, $lang_code = null): string
    {
        try {
            $lang_code = $lang_code ?: app()->getLocale();
            $lang_key = preg_replace('/[^A-Za-z0-9\_]/', '', str_replace(' ', '_', strtolower($keyWord)));
            $translate_data = TieredCache::remember('translations-' . $lang_code, 60 * 60, function () use ($lang_code) {
                return \app\model\Translation::where('code', $lang_code)->pluck('value', 'key')->toArray();
            });
            if (!array_key_exists($lang_key, $translate_data)) {
                $translate_val = str_replace(["\r", "\n", "\r\n"], '', $keyWord);
                \app\model\Translation::create([
                    'code'  => $lang_code,
                    'key'   => $lang_key,
                    'value' => $translate_val,
                ]);
                $keyWord = $translate_val;
                TieredCache::delete('translations-' . $lang_code);
            } else {
                $keyWord = $translate_data[$lang_key];
            }
        } catch (\Throwable $th) {
        }
        return ucfirst($keyWord);
    }
}

if (!function_exists('site_settings')) {
    /**
     * 获取站点设置
     */
    function site_settings(?string $key, mixed $default = null): int|string|array|object|null
    {
        return Arr::get(TieredCache::remember('site_settings', 24 * 60 * 60, function () {
            $settingService = new SettingService(new SettingRepository(new Setting()));
            return $settingService->getAllSettings(0);
        }), $key, $default);
    }
}

if (!function_exists('default_currency')) {
    /**
     * 获取默认货币
     */
    function default_currency()
    {
        return \app\utility\MoneyHelper::defaultCurrency();
    }
}

if (!function_exists('auth_user')) {
    /**
     * 获取当前认证用户（管理员）
     */
    function auth_user(): ?object
    {
        $admin = session()->get('admin');
        if ($admin && is_array($admin)) {
            return (object)$admin;
        }
        return null;
    }
}

if (!function_exists('auth_check')) {
    /**
     * 检查用户是否已认证
     */
    function auth_check(): bool
    {
        return session()->has('admin_id') && session()->has('admin');
    }
}

if (!function_exists('auth_id')) {
    /**
     * 获取认证用户ID
     */
    function auth_id(): mixed
    {
        return session()->get('admin_id');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * 获取/生成 CSRF Token
     */
    function csrf_token(): string
    {
        $token = session('_token');
        if (!$token) {
            try {
                $token = bin2hex(random_bytes(32));
            } catch (\Exception $e) {
                $token = md5(uniqid('', true));
            }
            session(['_token' => $token]);
        }
        return (string)$token;
    }
}

if (!function_exists('csrf_field')) {
    /**
     * 生成隐藏域 CSRF 字段
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('api')) {
    /**
     * 创建 API JSON 响应构建器
     */
    function api($data = []): \app\utility\ApiJsonResponse
    {
        return new \app\utility\ApiJsonResponse($data);
    }
}

if (!function_exists('permission_check')) {
    /**
     * 权限检查
     */
    function permission_check($permission): bool
    {
        $admin = session('admin');
        if (!$admin) {
            return false;
        }
        if (isset($admin['is_super_admin']) && $admin['is_super_admin']) {
            return true;
        }
        if (!isset($admin['role'])) {
            return true;
        }
        $permissions = format_permissions(json_decode($admin['role']['permissions'] ?? '[]', true));
        return in_array($permission, $permissions);
    }
}

if (!function_exists('format_permissions')) {
    /**
     * 格式化权限数组
     */
    function format_permissions($permissions): array
    {
        $permission_values = [];
        foreach ($permissions as $features) {
            foreach ($features as $feature) {
                $permission_values = array_merge($permission_values, $feature);
            }
        }
        return $permission_values;
    }
}

if (!function_exists('is_demo')) {
    /**
     * 检查是否为演示模式
     */
    function is_demo(): bool
    {
        return strtolower(env('APP_MODE')) === 'demo';
    }
}

if (!function_exists('guest_checkout')) {
    /**
     * 检查是否允许游客结账
     */
    function guest_checkout(): bool
    {
        return site_settings('guest_checkout') == StatusEnum::true->status();
    }
}

if (!function_exists('frontend_section')) {
    /**
     * 获取前端区块数据
     */
    function frontend_section($slug = null)
    {
        $frontends = TieredCache::remember('frontend', 24 * 60 * 60, fn () => \app\model\Frontend::get());
        if ($slug) {
            return $frontends->where('slug', $slug)->first();
        }
        return $frontends;
    }
}

if (!function_exists('get_translation')) {
    /**
     * 获取多语言翻译值
     */
    function get_translation($data, $lang = null)
    {
        $lang = $lang ?: session()->get('locale');
        $lang_data = (array)@json_decode($data, true);
        $default = Arr::get($lang_data, 'en', 'default');
        if (array_key_exists($lang, $lang_data ?? [])) {
            $transate = $lang_data[$lang];
        }
        return $transate ?? $default;
    }
}

if (!function_exists('translateable_locale')) {
    /**
     * 获取可翻译的语言代码列表（当前语言排在前面）
     */
    function translateable_locale(object $languages): array
    {
        $localeArray = $languages->pluck('code')->toArray();
        usort($localeArray, function ($a, $b) {
            $systemLocale = session()->get('locale');
            $systemLocaleIndex = array_search($systemLocale, [$a, $b]);
            return $systemLocaleIndex === false ? 0 : ($systemLocaleIndex === 0 ? -1 : 1);
        });
        return $localeArray;
    }
}

if (!function_exists('getLanguagesArr')) {
    /**
     * 获取语言名称映射数组
     */
    function getLanguagesArr(object $languages): array
    {
        return $languages->pluck('name', 'code')->toArray();
    }
}

if (!function_exists('get_ai_option')) {
    /**
     * 获取AI写作选项
     */
    function get_ai_option(): array
    {
        return [
            'improve_it' => ['prompt' => 'Improve the above message writing'],
            'Grammer Correction' => ['prompt' => 'Correct any grammatical mistake in the message'],
            'make_it_more_detailed' => ['prompt' => 'Make this message More Detailed'],
            'simplyfy_it' => ['prompt' => 'Simplyfy this message'],
            'make_it_informative' => ['prompt' => 'Make the message more informative'],
            'fix_any_mistake' => ['prompt' => 'Fix if there is any mistake in the message'],
            'sound_fluent' => ['prompt' => 'Make this message as it sound more fluent'],
            'make_it_objective' => ['prompt' => 'Make  this message more objective'],
        ];
    }
}

if (!function_exists('get_ai_tone')) {
    /**
     * 获取AI语气选项
     */
    function get_ai_tone(): array
    {
        return [
            'engaging' => ['display_name' => 'Make It Engaging', 'prompt' => 'Make the message content tone more engaging'],
            'sound_formal' => ['display_name' => 'Sound Formal', 'prompt' => 'Make the message content tone more formal'],
            'sound_casual' => ['display_name' => 'Sound Casual', 'prompt' => 'Make the message  content tone  sound more casual'],
            'friendly' => ['display_name' => 'Make It Friendly', 'prompt' => 'Make the message content tone more user friendly'],
            'exciting' => ['display_name' => 'Make It Exciting', 'prompt' => 'Make the message content tone more exciting'],
            'confident' => ['display_name' => 'Make It Confident', 'prompt' => 'Make the message content tone more Confident'],
            'assertive' => ['display_name' => 'Make It Assertive', 'prompt' => 'Make the message content tone more assertive'],
        ];
    }
}

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
    function current_route_url(array $parameters = null): string
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

if (!function_exists('generatePrefixedHash')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::generatePrefixedHash() 替代
     */
    function generatePrefixedHash(string $prefix = 'PL_'): string
    {
        return \app\utility\StringHelper::generatePrefixedHash($prefix);
    }
}

if (!function_exists('toUnderScore')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::toUnderScore() 替代
     */
    function toUnderScore($str): string
    {
        return \app\utility\StringHelper::toUnderScore($str);
    }
}

if (!function_exists('random_string')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::randomString() 替代
     */
    function random_string($length = 10, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'): string
    {
        return \app\utility\StringHelper::randomString($length);
    }
}

if (!function_exists('generate_order_no')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::generateOrderNo() 替代
     */
    function generate_order_no($prefix = 'YX'): string
    {
        return \app\utility\StringHelper::generateOrderNo($prefix);
    }
}

if (!function_exists('generateOrderCode')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::generateOrderCode() 替代
     */
    function generateOrderCode($length = 6): string
    {
        return \app\utility\StringHelper::generateOrderCode();
    }
}

if (!function_exists('hexa_to_rgba')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::hexaToRgba() 替代
     */
    function hexa_to_rgba($code): string
    {
        return \app\utility\StringHelper::hexaToRgba($code);
    }
}

if (!function_exists('trx_number')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::trxNumber() 替代
     */
    function trx_number($length = 14): string
    {
        return \app\utility\StringHelper::trxNumber($length);
    }
}

if (!function_exists('text_sorted')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::textSorted() 替代
     */
    function text_sorted($text): string
    {
        return \app\utility\StringHelper::textSorted($text);
    }
}

if (!function_exists('limit_lines')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::limitLines() 替代
     */
    function limit_lines($text, $limit, $end = '...'): string
    {
        return \app\utility\StringHelper::limitLines($text, $limit, $end);
    }
}

if (!function_exists('limit_words')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::limitWords() 替代
     */
    function limit_words($text, $limit, $end = '...'): string
    {
        return \app\utility\StringHelper::limitWords($text, $limit, $end);
    }
}

if (!function_exists('replace_sort_code')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::replaceSortCode() 替代
     */
    function replace_sort_code($message, $name = null, $replaceableCode = []): string
    {
        return \app\utility\StringHelper::replaceSortCode($message);
    }
}

if (!function_exists('rand_token')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::randToken() 替代
     */
    function rand_token($length = 10): string
    {
        return \app\utility\StringHelper::randToken($length);
    }
}

if (!function_exists('str_unique')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::strUnique() 替代
     */
    function str_unique(int $length = 16): string
    {
        return \app\utility\StringHelper::strUnique($length);
    }
}

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

if (!function_exists('unslug')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::unslug() 替代
     */
    function unslug($slug, $delimiter = '-'): string
    {
        return \app\utility\StringHelper::unslug($slug, $delimiter);
    }
}

// ---- MoneyHelper 转发 ----

if (!function_exists('format_money')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::formatMoney() 替代
     */
    function format_money($amount, $decimals = 2): string
    {
        return \app\utility\MoneyHelper::formatMoney($amount, $decimals);
    }
}

if (!function_exists('short_amount')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::shortAmount() 替代
     */
    function short_amount(mixed $amount, bool $showCurrency = true, bool $number_format = true): string
    {
        return \app\utility\MoneyHelper::shortAmount($amount, $showCurrency, $number_format);
    }
}

if (!function_exists('show_amount')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::showAmount() 替代
     */
    function show_amount(mixed $amount, string $symbol = null): string
    {
        return \app\utility\MoneyHelper::showAmount($amount, $symbol);
    }
}

if (!function_exists('api_short_amount')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::apiShortAmount() 替代
     */
    function api_short_amount(int|float $amount, int $length = 2): int|float
    {
        return \app\utility\MoneyHelper::apiShortAmount($amount, $length);
    }
}

if (!function_exists('convert_to_base')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::convertToBase() 替代
     */
    function convert_to_base($amount, $length = 2): int
    {
        return \app\utility\MoneyHelper::convertToBase($amount, $length);
    }
}

if (!function_exists('exchange_rate')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::exchangeRate() 替代
     */
    function exchange_rate(mixed $currency): int|float
    {
        return \app\utility\MoneyHelper::exchangeRate($currency);
    }
}

if (!function_exists('default_currency_converter')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::defaultCurrencyConverter() 替代
     */
    function default_currency_converter(int|float $amount, $currency): int|float
    {
        return \app\utility\MoneyHelper::defaultCurrencyConverter($amount, $currency);
    }
}

if (!function_exists('get_currency_symbol')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::getCurrencySymbol() 替代
     */
    function get_currency_symbol(): string
    {
        return \app\utility\MoneyHelper::getCurrencySymbol();
    }
}

if (!function_exists('get_currency_name')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::getCurrencyName() 替代
     */
    function get_currency_name(): string
    {
        return \app\utility\MoneyHelper::getCurrencyName();
    }
}

if (!function_exists('show_currency')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::showCurrency() 替代
     */
    function show_currency(): string
    {
        return \app\utility\MoneyHelper::showCurrency();
    }
}

if (!function_exists('discount')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::discount() 替代
     */
    function discount($total, $discount, $type = 0): float
    {
        return \app\utility\MoneyHelper::discount($total, $discount, $type);
    }
}

if (!function_exists('cal_discount')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::calDiscount() 替代
     */
    function cal_discount($discount, $price): float
    {
        return \app\utility\MoneyHelper::calDiscount($discount, $price);
    }
}

// ---- OrderHelper 转发 ----

if (!function_exists('order_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::orderStatus() 替代
     */
    function order_status(int|string $status): string
    {
        return \app\utility\OrderHelper::orderStatus($status);
    }
}

if (!function_exists('order_status_badge')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::orderStatusBadge() 替代
     */
    function order_status_badge(int|string $status): array
    {
        return \app\utility\OrderHelper::orderStatusBadge($status);
    }
}

if (!function_exists('delivery_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::deliveryStatus() 替代
     */
    function delivery_status(int|string $status): string
    {
        return \app\utility\OrderHelper::deliveryStatus($status);
    }
}

if (!function_exists('order_payment_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::orderPaymentStatus() 替代
     */
    function order_payment_status(int|string $paymentStatus): string
    {
        return \app\utility\OrderHelper::orderPaymentStatus($paymentStatus);
    }
}

if (!function_exists('product_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::productStatus() 替代
     */
    function product_status(int|string $status): string
    {
        return \app\utility\OrderHelper::productStatus($status);
    }
}

if (!function_exists('reward_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::rewardStatus() 替代
     */
    function reward_status(int|string $status): string
    {
        return \app\utility\OrderHelper::rewardStatus($status);
    }
}

if (!function_exists('show_ratings')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::showRatings() 替代
     */
    function show_ratings($ratings): string
    {
        return \app\utility\OrderHelper::showRatings($ratings);
    }
}

if (!function_exists('response_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::responseStatus() 替代
     */
    function response_status(string $message = 'Successfully Completed', string $key = 'success'): array
    {
        return \app\utility\OrderHelper::responseStatus($key === 'success' ? 1 : 0);
    }
}

if (!function_exists('update_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::updateStatus() 替代
     */
    function update_status($id, $modelName, $status, $columName = 'status'): string
    {
        return \app\utility\OrderHelper::updateStatus($status);
    }
}

if (!function_exists('mark_status_update')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::markStatusUpdate() 替代
     */
    function mark_status_update($modelName, $status, $column, $ids): string
    {
        return \app\utility\OrderHelper::markStatusUpdate($status);
    }
}

if (!function_exists('validateModelStatus')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::validateModelStatus() 替代
     */
    function validateModelStatus($request, array $modelInfo): bool
    {
        return \app\utility\OrderHelper::validateModelStatus($modelInfo['status'] ?? 0, $modelInfo['valid_statuses'] ?? [0, 1]);
    }
}

if (!function_exists('negative_value')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::negativeValue() 替代
     */
    function negative_value(int|float $value, $float = false): string
    {
        return \app\utility\OrderHelper::negativeValue($value);
    }
}

// ---- FileHelper 转发 ----

if (!function_exists('store_file')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::storeFile() 替代
     */
    function store_file($file, $location, $size = null, $removefile = null): string
    {
        return \app\utility\FileHelper::storeFile($file, $location, $size, $removefile);
    }
}

if (!function_exists('remove_file')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::removeFile() 替代
     */
    function remove_file($location, $removefile): void
    {
        \app\utility\FileHelper::removeFile($location, $removefile);
    }
}

if (!function_exists('show_image')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::showImage() 替代
     */
    function show_image($image, $size = null): string
    {
        return \app\utility\FileHelper::showImage($image, $size);
    }
}

if (!function_exists('upload_new_file')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::uploadNewFile() 替代
     */
    function upload_new_file($file, $location, $old = null): string
    {
        return \app\utility\FileHelper::uploadNewFile($file, $location, $old);
    }
}

if (!function_exists('export_excel')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::exportExcel() 替代
     */
    function export_excel($fileName, $tileArray = [], $dataArray = []): void
    {
        \app\utility\FileHelper::exportExcel($fileName, $tileArray, $dataArray);
    }
}

if (!function_exists('file_format')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::fileFormat() 替代
     */
    function file_format($type = 'image'): array
    {
        return \app\utility\FileHelper::fileFormat();
    }
}

if (!function_exists('file_path')) {
    /**
     * @deprecated 使用 \app\utility\FileHelper::filePath() 替代
     */
    function file_path(): array
    {
        return \app\utility\FileHelper::filePath();
    }
}

// ---- HttpHelper 转发 ----

if (!function_exists('curl')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::curl() 替代
     */
    function curl($url, $data = [])
    {
        return \app\utility\HttpHelper::curl($url, $data);
    }
}

if (!function_exists('curlPost')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::curlPost() 替代
     */
    function curlPost($url, $data = [])
    {
        return \app\utility\HttpHelper::curlPost($url, $data);
    }
}

if (!function_exists('build_post_fields')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::buildPostFields() 替代
     */
    function build_post_fields($data, $existingKeys = '', &$returnArray = []): array
    {
        return \app\utility\HttpHelper::buildPostFields($data, $existingKeys, $returnArray);
    }
}

if (!function_exists('array_to_xml')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::arrayToXml() 替代
     */
    function array_to_xml($data, $root = 'xml'): string
    {
        return \app\utility\HttpHelper::arrayToXml($data, $root);
    }
}

if (!function_exists('xml_to_array')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::xmlToArray() 替代
     */
    function xml_to_array($xml): array
    {
        return \app\utility\HttpHelper::xmlToArray($xml);
    }
}

if (!function_exists('get_client_ip')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::getClientIp() 替代
     */
    function get_client_ip(): string
    {
        return \app\utility\HttpHelper::getClientIp();
    }
}

if (!function_exists('get_real_ip')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::getRealIp() 替代
     */
    function get_real_ip(): string
    {
        return \app\utility\HttpHelper::getRealIp();
    }
}

if (!function_exists('get_ip_info')) {
    /**
     * @deprecated 使用 \app\utility\HttpHelper::getIpInfo() 替代
     */
    function get_ip_info(): array
    {
        return \app\utility\HttpHelper::getIpInfo();
    }
}

// ---- DateHelper 转发 ----

if (!function_exists('diff_for_humans')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::diffForHumans() 替代
     */
    function diff_for_humans($date): string
    {
        return \app\utility\DateHelper::diffForHumans($date);
    }
}

if (!function_exists('get_date_time')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::getDateTime() 替代
     */
    function get_date_time($date, $format = 'Y-m-d h:i A'): string
    {
        return \app\utility\DateHelper::getDateTime($date, $format);
    }
}

if (!function_exists('days_in_year')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::daysInYear() 替代
     */
    function days_in_year($year = null): int
    {
        return \app\utility\DateHelper::daysInYear($year);
    }
}

if (!function_exists('days_in_month')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::daysInMonth() 替代
     */
    function days_in_month($month = null, $year = null): int
    {
        return \app\utility\DateHelper::daysInMonth($month, $year);
    }
}

if (!function_exists('sort_by_month')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::sortByMonth() 替代
     */
    function sort_by_month(array $data, array $keys = null): array
    {
        return \app\utility\DateHelper::sortByMonth($data);
    }
}

if (!function_exists('distanceInWords')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::distanceInWords() 替代
     */
    function distanceInWords($distance): string
    {
        return \app\utility\DateHelper::distanceInWords((int)$distance);
    }
}

if (!function_exists('calculateDistance')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::calculateDistance() 替代
     */
    function calculateDistance($formAddress, $toAddress): float
    {
        return \app\utility\DateHelper::calculateDistance(
            (float)($formAddress['lat'] ?? 0),
            (float)($formAddress['lng'] ?? 0),
            (float)($toAddress['lat'] ?? 0),
            (float)($toAddress['lng'] ?? 0)
        );
    }
}
