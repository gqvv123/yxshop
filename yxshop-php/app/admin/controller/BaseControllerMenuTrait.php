<?php

namespace app\admin\controller;
use support\Request;
use Illuminate\Support\Collection;
use Exception;

/**
 * BaseControllerMenuTrait：从 BaseController.php 抽离的getRouteInfo、menu、getDefaultMenu、transformMenus、transformMenuItem、normalizeMenuPath、pathMatches等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\admin\controller
 */
trait BaseControllerMenuTrait
{
    /**
     * 解析当前路由参数
     */
    protected function getRouteInfo(Request $request): void
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
    private function getDefaultMenu(): array
    {
        return [
            [
                'name' => '仪表盘',
                'model' => 'dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'url' => '/dashboard',
                'children' => [],
            ],
            [
                'name' => '订单管理',
                'model' => 'order',
                'icon' => 'fas fa-shopping-cart text-warning',
                'children' => [
                    [
                        'name' => '订单列表',
                        'url' => '/order',
                        'icon' => 'fa fa-shopping-cart'
                    ],
                    [
                        'name' => '订单跟踪',
                        'url' => '/order',
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
                        'url' => '/item',
                        'icon' => 'fas fa-boxes'
                    ],
                    [
                        'name' => '商品分类',
                        'url' => '/category',
                        'icon' => 'fas fa-bullseye'
                    ],
                    [
                        'name' => '品牌管理',
                        'url' => '/brand',
                        'icon' => 'fas fa-tag'
                    ],
                    [
                        'name' => '规格管理',
                        'url' => '/specs',
                        'icon' => 'fas fa-th-list'
                    ],
                    [
                        'name' => '商品标签',
                        'url' => '/item-tags',
                        'icon' => 'fas fa-tags'
                    ],
                    [
                        'name' => '商品类型',
                        'url' => '/item-types',
                        'icon' => 'fas fa-layer-group'
                    ],
                    [
                        'name' => '商品属性',
                        'url' => '/item-attributes',
                        'icon' => 'fas fa-attributes'
                    ],
                    [
                        'name' => '评价管理',
                        'url' => '/comment',
                        'icon' => 'fas fa-comments'
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
                        'url' => '/user',
                        'icon' => 'fas fa-users'
                    ],
                    [
                        'name' => '用户等级',
                        'url' => '/user-levels',
                        'icon' => 'fas fa-medal'
                    ],
                    [
                        'name' => '用户地址',
                        'url' => '/user-addresses',
                        'icon' => 'far fa-address-book'
                    ],
                    [
                        'name' => '用户优惠券',
                        'url' => '/user-coupons',
                        'icon' => 'fas fa-ticket-alt'
                    ],
                    [
                        'name' => '余额日志',
                        'url' => '/user-money-logs',
                        'icon' => 'fas fa-yen-sign'
                    ],
                    [
                        'name' => '用户日志',
                        'url' => '/user-logs',
                        'icon' => 'fas fa-history'
                    ],
                    [
                        'name' => '用户反馈',
                        'url' => '/user-feedback',
                        'icon' => 'fas fa-comment-dots'
                    ]
                ]
            ],
            [
                'name' => '优惠券管理',
                'model' => 'coupon',
                'icon' => 'fa fa-puzzle-piece text-warning',
                'url' => '/coupon',
                'children' => [],
            ],
            [
                'name' => '营销管理',
                'model' => 'marketing',
                'icon' => 'fas fa-chart-line text-info',
                'children' => [
                    [
                        'name' => '营销概览',
                        'url' => '/marketing',
                        'icon' => 'fas fa-bullhorn'
                    ],
                    [
                        'name' => '促销活动',
                        'url' => '/promotions',
                        'icon' => 'fas fa-gift'
                    ],
                    [
                        'name' => '商品促销',
                        'url' => '/prom-items',
                        'icon' => 'fas fa-tags'
                    ],
                    [
                        'name' => '订单促销',
                        'url' => '/prom-orders',
                        'icon' => 'fas fa-shopping-bag'
                    ]
                ]
            ],
            [
                'name' => '财务管理',
                'model' => 'finance',
                'icon' => 'fas fa-calculator text-danger',
                'children' => [
                    [
                        'name' => '财务统计',
                        'url' => '/finance',
                        'icon' => 'fas fa-chart-pie'
                    ],
                    [
                        'name' => '支付记录',
                        'url' => '/payment',
                        'icon' => 'fas fa-credit-card'
                    ],
                    [
                        'name' => '支付日志',
                        'url' => '/payment-logs',
                        'icon' => 'fas fa-receipt'
                    ],
                    [
                        'name' => '充值套餐',
                        'url' => '/recharge-package',
                        'icon' => 'fas fa-wallet'
                    ]
                ]
            ],
            [
                'name' => '内容管理',
                'model' => 'content',
                'icon' => 'fas fa-newspaper text-info',
                'children' => [
                    [
                        'name' => '文章列表',
                        'url' => '/article',
                        'icon' => 'fas fa-file-alt'
                    ],
                    [
                        'name' => '文章分类',
                        'url' => '/article-category',
                        'icon' => 'fas fa-folder'
                    ],
                    [
                        'name' => '内容页面',
                        'url' => '/content-pages',
                        'icon' => 'fas fa-file'
                    ],
                    [
                        'name' => '页面SEO',
                        'url' => '/page-seo',
                        'icon' => 'fas fa-search'
                    ],
                    [
                        'name' => '友情链接',
                        'url' => '/friendly-link',
                        'icon' => 'fas fa-link'
                    ],
                    [
                        'name' => '广告管理',
                        'url' => '/advertisements',
                        'icon' => 'fas fa-bullhorn'
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
                        'url' => '/setting',
                        'icon' => 'fas fa-store'
                    ],
                    [
                        'name' => '配送模板',
                        'url' => '/deliveries',
                        'icon' => 'fas fa-shipping-fast'
                    ],
                    [
                        'name' => '配送规则',
                        'url' => '/delivery-rule',
                        'icon' => 'fas fa-truck'
                    ],
                    [
                        'name' => '快递管理',
                        'url' => '/express',
                        'icon' => 'fas fa-box'
                    ],
                    [
                        'name' => '地区管理',
                        'url' => '/regions',
                        'icon' => 'fas fa-map'
                    ],
                    [
                        'name' => '货币管理',
                        'url' => '/currencies',
                        'icon' => 'fas fa-coins'
                    ],
                    [
                        'name' => '语言管理',
                        'url' => '/language',
                        'icon' => 'fas fa-language'
                    ]
                ]
            ],
            [
                'name' => '通知管理',
                'model' => 'notification',
                'icon' => 'fas fa-bell text-warning',
                'children' => [
                    [
                        'name' => '通知模板',
                        'url' => '/notification-template',
                        'icon' => 'fas fa-file-code'
                    ],
                    [
                        'name' => '通知场景',
                        'url' => '/notification-scenes',
                        'icon' => 'fas fa-broadcast-tower'
                    ],
                    [
                        'name' => '发送记录',
                        'url' => '/notification-sends',
                        'icon' => 'fas fa-paper-plane'
                    ],
                    [
                        'name' => '通知配置',
                        'url' => '/notification-configs',
                        'icon' => 'fas fa-cog'
                    ]
                ]
            ],
            [
                'name' => '日志管理',
                'model' => 'log',
                'icon' => 'fas fa-history text-info',
                'children' => [
                    [
                        'name' => '操作日志',
                        'url' => '/log',
                        'icon' => 'fas fa-file-alt'
                    ],
                    [
                        'name' => '短信日志',
                        'url' => '/sms-logs',
                        'icon' => 'fas fa-sms'
                    ],
                    [
                        'name' => '邮件日志',
                        'url' => '/email-logs',
                        'icon' => 'fas fa-envelope'
                    ],
                    [
                        'name' => '文件日志',
                        'url' => '/file-logs',
                        'icon' => 'fas fa-file'
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
                        'url' => '/admin',
                        'icon' => 'fas fa-user-tie'
                    ],
                    [
                        'name' => '角色管理',
                        'url' => '/role',
                        'icon' => 'fas fa-user-tag'
                    ],
                    [
                        'name' => '菜单管理',
                        'url' => '/menu',
                        'icon' => 'fas fa-list'
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
}
