<?php

namespace app\api\controller;

use support\Request;
use app\service\MiniPageService;
use app\service\MiniThemeService;
use app\service\MiniTabBarService;

class MiniPageController
{
    protected $pageService;
    protected $themeService;
    protected $tabBarService;

    public function __construct()
    {
        $this->pageService = new MiniPageService();
        $this->themeService = new MiniThemeService();
        $this->tabBarService = new MiniTabBarService();
    }

    public function home(Request $request)
    {
        $appId = (int) $request->input('app_id', 0);

        if ($appId <= 0) {
            return $this->fallback();
        }

        try {
            $cacheKey = "mini_page:home:{$appId}";
            $cached = $this->getCache($cacheKey);
            if ($cached !== null) {
                return json(['code' => 0, 'data' => $cached]);
            }

            $page = $this->pageService->getPublishedHomePage($appId);

            if (!$page) {
                return $this->fallback();
            }

            $theme = null;
            if ($page->theme_id) {
                try {
                    $theme = $this->themeService->find($page->theme_id);
                } catch (\Throwable $e) {
                }
            }

            $tabBar = $this->tabBarService->getTabBar($appId);

            $result = [
                'page' => [
                    'id' => $page->id,
                    'page_name' => $page->page_name,
                    'page_data' => $page->page_data ?? [],
                    'status' => $page->status,
                ],
                'theme' => $theme ? [
                    'primary_color' => $theme->primary_color,
                    'secondary_color' => $theme->secondary_color,
                    'nav_background_color' => $theme->nav_background_color,
                    'nav_text_color' => $theme->nav_text_color,
                ] : null,
                'tabbar' => $tabBar ? [
                    'color' => $tabBar->color,
                    'selected_color' => $tabBar->selected_color,
                    'background_color' => $tabBar->background_color,
                    'border_style' => $tabBar->border_style,
                    'items' => $tabBar->items,
                ] : null,
            ];

            $this->setCache($cacheKey, $result, 60);

            return json(['code' => 0, 'data' => $result]);
        } catch (\Throwable $e) {
            return $this->fallback();
        }
    }

    protected function fallback()
    {
        // 从内置企业级模板读取完整布局
        $templateFile = base_path() . '/database/templates/home_enterprise.json';
        $pageData = [];

        if (is_file($templateFile)) {
            $raw = file_get_contents($templateFile);
            $tpl = json_decode($raw, true);
            if (is_array($tpl) && isset($tpl['page_data']) && is_array($tpl['page_data'])) {
                $pageData = $tpl['page_data'];
            }
        }

        // 如果模板读取失败，使用最精简的兜底
        if (empty($pageData)) {
            $pageData = [
                ['component_type' => 'search_bar', 'component_id' => 'fallback_search', 'sort' => 1, 'is_visible' => true, 'props' => ['placeholder' => '搜索商品', 'style' => 'rounded']],
            ];
        }

        return json([
            'code' => 0,
            'data' => [
                'page' => [
                    'page_name' => '首页',
                    'page_data' => $pageData,
                    'status' => 'fallback',
                ],
                'theme' => [
                    'primary_color' => '#2563eb',
                    'secondary_color' => '#3b82f6',
                    'nav_background_color' => '#1e3a8a',
                    'nav_text_color' => 'light',
                ],
                'tabbar' => [
                    'color' => '#94a3b8',
                    'selected_color' => '#2563eb',
                    'background_color' => '#FFFFFF',
                    'border_style' => 'white',
                    'items' => [
                        ['text' => '首页', 'icon_path' => '', 'selected_icon_path' => '', 'page_path' => '/pages/home/home', 'require_login' => false],
                        ['text' => '分类', 'icon_path' => '', 'selected_icon_path' => '', 'page_path' => '/pages/category/category', 'require_login' => false],
                        ['text' => '购物车', 'icon_path' => '', 'selected_icon_path' => '', 'page_path' => '/pages/cart/cart', 'require_login' => true],
                        ['text' => '订单', 'icon_path' => '', 'selected_icon_path' => '', 'page_path' => '/pages/order/list/list', 'require_login' => false],
                        ['text' => '我的', 'icon_path' => '', 'selected_icon_path' => '', 'page_path' => '/pages/profile/profile', 'require_login' => false],
                    ],
                ],
            ],
        ]);
    }

    protected function getCache($key)
    {
        try {
            $data = \support\Redis::get($key);
            return $data ? json_decode($data, true) : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function setCache($key, $data, $ttl = 60)
    {
        try {
            \support\Redis::setex($key, $ttl, json_encode($data, JSON_UNESCAPED_UNICODE));
        } catch (\Throwable $e) {
        }
    }
}