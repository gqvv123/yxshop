<?php

namespace app\admin\controller;

use support\Request;
use app\service\AdminService;
use app\service\AdminMenuService;
use app\service\AdminAuthService;
use app\service\CurrencyService;
use app\service\LanguageService;
use app\service\OrderService;
use app\traits\ApiResponseTrait;

class BaseController
{
    use BaseControllerMenuTrait;

    use ApiResponseTrait;
    /* @var array $admin 管理员登录信息 */
    protected $admin;

    /* @var string $app_id 应用ID */
    protected $app_id;

    /* @var string $controller 当前控制器名称 */
    protected $controller = '';

    /* @var string $action 当前方法名称 */
    protected $action = '';

    /* @var string $routeUri 当前路由uri */
    protected $routeUri = '';

    /* @var string $group 当前路由：分组名称 */
    protected $group = '';

    /* @var array $allowAllAction 全局白名单 */
    protected $allowAllAction = [
        'login/login',
        'login/captcha',
    ];

    private array $serviceInstances = [];

    public function __construct()
    {
    }

    public function __get(string $name)
    {
        if (array_key_exists($name, $this->serviceInstances)) {
            return $this->serviceInstances[$name];
        }

        $serviceMap = [
            'adminService' => AdminService::class,
            'menuService' => AdminMenuService::class,
            'authService' => AdminAuthService::class,
            'currencyService' => CurrencyService::class,
            'languageService' => LanguageService::class,
            'orderService' => OrderService::class,
        ];

        if (isset($serviceMap[$name])) {
            $this->serviceInstances[$name] = new $serviceMap[$name]();
            return $this->serviceInstances[$name];
        }

        throw new \Exception("Property {$name} not found");
    }

    /**
     * 后台初始化
     */
    public function initialize(Request $request): void
    {
        // 当前路由信息
        $this->getRouteInfo($request);

        // 管理员登录信息（优先从当前请求的 session 获取，其次从中间件）
        $sessionInstance = $request->session();
        $sessionAdmin = $sessionInstance ? $sessionInstance->get('admin') : null;
        $this->admin = $sessionAdmin ?? $request->admin ?? [];

        $this->app_id = $this->admin['app_id'] ?? 0;
    }

    /**
     * 权限验证
     */
    public function auth(Request $request)
    {
        $url = 'admin/' . $this->routeUri;

        if ($this->authService->check($url, $this->admin) === false) {
            if ($request->isAjax()) {
                return $this->renderError('权限不足');
            }
            return $this->renderError('权限不足');
        }
    }

    /**
     * 验证登录状态
     */
    private function checkLogin(Request $request)
    {
        // 验证当前请求是否在白名单
        if (in_array($this->routeUri, $this->allowAllAction)) {
            return true;
        }

        // 验证登录状态
        if (empty($this->admin)) {
            return redirect('/admin/login');
        }

        // 验证权限
        $this->auth($request);
        return true;
    }

    /**
     * 解析当前登录管理员。
     * 注意：BaseController::initialize() 在框架里没有调用点，$this->admin 常为空，
     * 因此优先用中间件注入的 $request->admin。
     */
    protected function resolveAdmin(?Request $request = null)
    {
        if ($request !== null && !empty($request->admin)) {
            return $request->admin;
        }
        if (!empty($this->admin)) {
            return $this->admin;
        }
        return [];
    }

    /**
     * 当前登录管理员的显示名称
     * 管理员表只有 nickname / username，没有 name 字段，统一在这里兜底，
     * 避免各控制器写 $this->admin['name'] 取到 null 导致非空列写入失败。
     */
    protected function getAdminName(?Request $request = null): string
    {
        $admin = $this->resolveAdmin($request);
        $nickname = $admin['nickname'] ?? null;
        if (is_string($nickname) && $nickname !== '') {
            return $nickname;
        }
        $username = $admin['username'] ?? null;
        if (is_string($username) && $username !== '') {
            return $username;
        }
        return '管理员';
    }

    /**
     * 当前登录管理员 ID
     */
    protected function getAdminId(?Request $request = null): int
    {
        $admin = $this->resolveAdmin($request);
        return (int) ($admin['id'] ?? 0);
    }

    /**
     * 获取当前app_id
     */
    protected function getAppId(?Request $request = null)
    {
        $admin = $this->resolveAdmin($request);
        return (int) ($admin['app_id'] ?? 0);
    }

    /**
     * 返回封装后的 API 数据到客户端
     */
    protected function renderJson($code = 1, $msg = '', $url = '', $data = [])
    {
        return json(['code' => $code, 'msg' => $msg, 'url' => $url, 'data' => $data]);
    }

    /**
     * @deprecated Use $this->success() from ApiResponseTrait
     */
    protected function renderSuccess($msg = 'success', $url = '', $data = [])
    {
        if ($url !== '') {
            $data['url'] = $url;
        }
        return $this->success($data, $msg);
    }

    /**
     * @deprecated Use $this->error() from ApiResponseTrait
     */
    protected function renderError($msg = 'error', $url = '', $data = [])
    {
        if ($url !== '') {
            $data['url'] = $url;
        }
        return $this->error($msg, 1, $data);
    }

    /**
     * 获取post数据
     */
    protected function postData(Request $request, $key)
    {
        return $request->post($key);
    }

}
