<?php

namespace app\admin\controller;

use support\Request;
use support\Response;
use app\service\AdminService;
use app\service\AdminMenuService;
use app\service\AdminAuthService;
use app\service\CurrencyService;
use app\service\LanguageService;
use app\service\OrderService;
use app\traits\ApiResponseTrait;
use Illuminate\Support\Collection;
use Exception;

class BaseController
{
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
        'wechat_auth/qrcode',
        'wechat_auth/scan-status',
    ];
    
    /* @var array $notLayoutAction 无需全局layout */
    protected $notLayoutAction = [
        'login/login',
        'login/captcha'
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
    public function initialize(Request $request)
    {
        // 当前路由信息
        $this->getRouteInfo($request);
        
        // 管理员登录信息（优先从当前请求的 session 获取，其次从中间件）
        $sessionInstance = $request->session();
        $sessionAdmin = $sessionInstance ? $sessionInstance->get('admin') : null;
        $this->admin = $sessionAdmin ?? $request->admin ?? [];

        $this->app_id = $this->admin['app_id'] ?? 0;
        
        // 全局layout
        $this->layout($request);
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
     * 全局layout模板输出
     */
    private function layout(Request $request)
    {
        // 验证当前请求是否在白名单
        if (!in_array($this->routeUri, $this->notLayoutAction)) {
            // 获取基础URL
            $protocol = $request->header('x-forwarded-proto') ?: 'http';
            $host = $request->host();
            $baseUrl = $protocol . '://' . $host;
            
            // 获取货币列表（通过 Service）
            $currencys = collect([]);
            $currency = (object)['name' => 'CNY', 'symbol' => '¥', 'id' => 1];
            
            try {
                $currencys = $this->currencyService->getActive();
                if ($currencys && $currencys->count() > 0) {
                    $currency = $currencys->first();
                }
            } catch (\Exception $e) {
                $currencys = collect([]);
            }
            
            // 获取语言列表（通过 Service）
            $languages = collect([]);
            $language = (object)['name' => '简体中文', 'code' => 'zh-CN', 'id' => 1];
            
            try {
                $languages = $this->languageService->getActive();
                if ($languages && $languages->count() > 0) {
                    $language = $this->languageService->getCurrentLanguage() ?? $languages->first();
                }
            } catch (\Exception $e) {
                $languages = collect([]);
            }
            
            // 获取订单统计（通过 Service/Repository）
            $physical_product_order_count = 0;
            $digital_product_order_count = 0;
            
            try {
                $appId = $this->admin['app_id'] ?? 0;
                // 通过 Service 获取待处理订单数量
                // 暂时简化：如果 OrderService 没有对应方法，设为 0
                $physical_product_order_count = 0;
                $digital_product_order_count = 0;
            } catch (\Exception $e) {
            }
            
            // OpenAI 配置（如果需要）
            $openAi = (object)[
                'status' => 0,
                'model' => 'gpt-3.5-turbo',
                'api_key' => ''
            ];
            
            try {
                // 尝试从配置或数据库获取 OpenAI 设置
                $openAiStatus = site_settings('openai_status') ?? 0;
                if ($openAiStatus) {
                    $openAi->status = 1;
                    $openAi->model = site_settings('openai_model') ?? 'gpt-3.5-turbo';
                    $openAi->api_key = site_settings('openai_api_key') ?? '';
                }
            } catch (\Exception $e) {
            }
            
            // 卖家新品统计（默认 0，后续可由 Service 填充）
            $seller_new_digital_product_count = 0;
            $seller_new_physical_product_count = 0;
            $physical_product_seller_order_count = 0;
            $digital_product_seller_order_count = 0;

            $currentPath = trim($request->path(), '/');
            $menuTree = $this->menu($currentPath);

            // 输出到view
            View::assign([
                'base_url' => $baseUrl,
                'admin_url' => '/admin',
                'group' => $this->group,
                'url' => $request->path(),
                'menu' => $menuTree,
                'admin' => $this->admin,
                'currencys' => $currencys,
                'currency' => $currency,
                'languages' => $languages,
                'language' => $language,
                'physical_product_order_count' => $physical_product_order_count,
                'digital_product_order_count' => $digital_product_order_count,
                'seller_new_digital_product_count' => $seller_new_digital_product_count,
                'seller_new_physical_product_count' => $seller_new_physical_product_count,
                'physical_product_seller_order_count' => $physical_product_seller_order_count,
                'digital_product_seller_order_count' => $digital_product_seller_order_count,
                'openAi' => $openAi,
                // 避免与全局 helper request() 冲突
                'http_request' => $request
            ]);
        }
    }

    /**
     * 解析当前路由参数
     */
    protected function getRouteInfo(Request $request)
    {
        $path = $request->path();
        $pathArray = explode('/', trim($path, '/'));
        
        // 控制器名称
        $this->controller = $pathArray[1] ?? 'index';
        // 方法名称
        $this->action = $pathArray[2] ?? 'index';
        // 控制器分组
        $this->group = $this->controller;
        // 当前uri
        $this->routeUri = $this->controller . '/' . $this->action;
    }

    /**
     * 后台菜单配置
     */
    private function menu(string $currentPath = '')
    {
        // 从数据库读取菜单数据
        try {
            $appId = $this->getAppId();
 
            // 通过 MenuService 获取菜单
            $menus = $this->menuService->getMenuTree($appId);
 
            if (empty($menus)) {
                $defaultMenu = $this->getDefaultMenu();
                return $this->transformMenus($defaultMenu, $currentPath);
            }            

            $menus = build_menu_tree($menus->toArray());

            return $this->transformMenus($menus, $currentPath);
        } catch (Exception $e) {
            // 如果数据库读取失败，返回默认菜单
            $defaultMenu = $this->getDefaultMenu();
            return $this->transformMenus($defaultMenu, $currentPath);
        }
    }
    
    /**
     * 获取默认菜单（备用）
     */
    private function getDefaultMenu()
    {
        return [
            [
                'name' => '仪表盘',
                'model' => 'dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => '/admin',
                'children' => [],
            ],
            [
                'name' => '订单管理',
                'model' => 'order',
                'icon' => 'fas fa-shopping-cart text-warning',
                'children' => [
                    [
                        'name' => '订单列表',
                        'url' => '/admin/order',
                        'icon' => 'fa fa-shopping-cart'
                    ],
                    [
                        'name' => '订单跟踪',
                        'url' => '/admin/order/tracking',
                        'icon' => 'fa fa-map-marker-alt'
                    ]
                ]
            ],
            [
                'name' => '商品管理',
                'model' => 'item',
                'icon' => 'fas fa-cubes text-primary',
                'children' => [
                    [
                        'name' => '商品列表',
                        'url' => '/admin/item',
                        'icon' => 'fas fa-boxes'
                    ],
                    [
                        'name' => '添加商品',
                        'url' => '/admin/item/create',
                        'icon' => 'fas fa-plus-square'
                    ],
                    [
                        'name' => '商品分类',
                        'url' => '/admin/category',
                        'icon' => 'fas fa-bullseye'
                    ]
                ]
            ],
            [
                'name' => '用户管理',
                'model' => 'user',
                'icon' => 'fa fa-user text-success',
                'children' => [
                    [
                        'name' => '用户列表',
                        'url' => '/admin/user',
                        'icon' => 'fas fa-users'
                    ],
                    [
                        'name' => '用户地址',
                        'url' => '/admin/user/addresses',
                        'icon' => 'far fa-address-book'
                    ]
                ]
            ],
            [
                'name' => '优惠券管理',
                'model' => 'coupon',
                'icon' => 'fa fa-puzzle-piece text-warning',
                'url' => '/admin/coupon',
                'children' => [],
            ],
            [
                'name' => '营销管理',
                'model' => 'marketing',
                'icon' => 'fas fa-chart-line text-info',
                'children' => [
                    [
                        'name' => '促销活动',
                        'url' => '/admin/marketing/promotions',
                        'icon' => 'fas fa-gift'
                    ],
                    [
                        'name' => '消息推送',
                        'url' => '/admin/marketing/notifications',
                        'icon' => 'fas fa-bell'
                    ]
                ]
            ],
            [
                'name' => '财务管理',
                'model' => 'finance',
                'icon' => 'fas fa-calculator text-danger',
                'children' => [
                    [
                        'name' => '事务记录',
                        'url' => '/admin/finance/transactions',
                        'icon' => 'fas fa-money-bill-wave'
                    ],
                    [
                        'name' => '财务统计',
                        'url' => '/admin/finance/statistics',
                        'icon' => 'fas fa-chart-pie'
                    ]
                ]
            ],
            [
                'name' => '系统设置',
                'model' => 'setting',
                'icon' => 'fa fa-wrench text-primary',
                'children' => [
                    [
                        'name' => '基本设置',
                        'url' => '/admin/setting',
                        'icon' => 'fas fa-store'
                    ],
                    [
                        'name' => '支付设置',
                        'url' => '/admin/setting/payment',
                        'icon' => 'fas fa-credit-card'
                    ],
                    [
                        'name' => '配送设置',
                        'url' => '/admin/setting/shipping',
                        'icon' => 'fas fa-shipping-fast'
                    ],
                    [
                        'name' => '通知设置',
                        'url' => '/admin/setting/notification',
                        'icon' => 'fa fa-bell'
                    ]
                ]
            ],
            [
                'name' => '权限管理',
                'model' => 'permission',
                'icon' => 'fas fa-shield-alt text-warning',
                'children' => [
                    [
                        'name' => '管理员',
                        'url' => '/admin/admin',
                        'icon' => 'fas fa-user-tie'
                    ],
                    [
                        'name' => '角色管理',
                        'url' => '/admin/role',
                        'icon' => 'fas fa-user-tag'
                    ]
                ]
            ],
            [
                'name' => '数据统计',
                'model' => 'report',
                'icon' => 'fas fa-chart-pie text-primary',
                'children' => [
                    [
                        'name' => '销售报表',
                        'url' => '/admin/report/sales',
                        'icon' => 'fa fa-chart-line'
                    ],
                    [
                        'name' => '用户统计',
                        'url' => '/admin/report/user',
                        'icon' => 'fa fa-users'
                    ]
                ]
            ]
        ];
    }

    private function transformMenus($menus, string $currentPath): array
    {
        $result = [];

        if ($menus instanceof Collection) {
            foreach ($menus as $menu) {
                $formatted = $this->transformMenuItem($menu, $currentPath);
                if ($formatted !== null) {
                    $result[] = $formatted;
                }
            }
            return $result;
        }

        if (is_array($menus)) {
            foreach ($menus as $menu) {
                $formatted = $this->transformMenuItem($menu, $currentPath);
                if ($formatted !== null) {
                    $result[] = $formatted;
                }
            }
        }

        return $result;
    }

    private function transformMenuItem($menu, string $currentPath)
    {
        if (is_object($menu) && !($menu instanceof Collection)) {
            $meta = $menu->meta ?? [];
            if (is_string($meta)) {
                $decoded = json_decode($meta, true);
                $meta = json_last_error() === JSON_ERROR_NONE ? $decoded : [];
            }
            $rawChildren = $menu->children ?? collect();
            $item = [
                'id' => $menu->id,
                'name' => $menu->name,
                'icon' => $menu->icon ?: 'ri-circle-line',
                'url' => $menu->url,
                'model' => $menu->model,
                'meta' => $meta ?: [],
            ];
        } elseif (is_array($menu)) {
            $rawChildren = $menu['children'] ?? $menu['list'] ?? [];
            $item = [
                'id' => $menu['id'] ?? null,
                'name' => $menu['name'] ?? '',
                'icon' => $menu['icon'] ?? 'ri-circle-line',
                'url' => $menu['url'] ?? '',
                'model' => $menu['model'] ?? null,
                'meta' => $menu['meta'] ?? [],
            ];
        } else {
            return null;
        }

        $item['children'] = $this->transformMenus($rawChildren, $currentPath);
        $itemPath = $this->normalizeMenuPath($item['url']);
        $isDirectMatch = $this->pathMatches($currentPath, $itemPath);

        $hasOpenChild = false;
        foreach ($item['children'] as $child) {
            if (!empty($child['open'])) {
                $hasOpenChild = true;
                break;
            }
        }

        $item['active'] = $isDirectMatch;
        $item['open'] = $isDirectMatch || $hasOpenChild;

        return $item;
    }

    private function normalizeMenuPath($path): string
    {
        if (empty($path)) {
            return '';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }
        return trim($path, '/');
    }

    private function pathMatches(string $currentPath, string $targetPath): bool
    {
        if ($targetPath === '') {
            return false;
        }

        if (str_starts_with($targetPath, 'http://') || str_starts_with($targetPath, 'https://')) {
            return false;
        }

        $current = trim($currentPath, '/');
        $target = trim($targetPath, '/');

        if ($target === '') {
            return $current === '';
        }

        return $current === $target || str_starts_with($current, $target . '/');
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
     * 获取当前app_id
     */
    protected function getAppId(Request $request = null)
    {
        if ($request && isset($request->admin)) {
            return $request->admin['app_id'] ?? 10001;
        }
        return $this->admin['app_id'] ?? 10001;
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
