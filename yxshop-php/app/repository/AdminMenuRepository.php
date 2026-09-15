<?php

namespace app\repository;

use app\model\AdminMenu;

/**
 * 菜单仓储类
 */
class AdminMenuRepository extends BaseRepository
{
    protected $model = AdminMenu::class;

    /**
     * 获取菜单树
     */
    public function getMenuTree($parentId = 0, $appId = 0)
    {        
        $query = $this->query()
            ->where('parent_id', $parentId)
            ->where('is_show', 1)
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        $menus = $query->get();

        foreach ($menus as $menu) {
            $menu->children = $this->getMenuTree($menu->id, $appId);
        }

        return $menus;
    }

    public function getAdminMenuTree(array $menuIds = [], bool $isSuperAdmin = false, int $appId = 10001): array
    {
        $query = $this->query()
            ->where('is_show', 1)
            ->where('app_id', $appId)
            ->orderBy('sort', 'asc');

        if (!$isSuperAdmin && !empty($menuIds)) {
            $query->whereIn('id', $menuIds);
        }

        $allMenus = $query->get();

        return $this->buildFrontendTree($allMenus, 0);
    }

    private function buildFrontendTree($menus, $parentId): array
    {
        $tree = [];
        $children = $menus->where('parent_id', $parentId);

        foreach ($children as $menu) {
            $hasUrl = !empty($menu->url);
            $component = 'empty';
            if ($hasUrl && !empty($menu->permission)) {
                $component = $menu->permission;
            } elseif ($hasUrl) {
                $component = trim($menu->url, '/') . '/index';
            }

            $routeName = $menu->permission
                ? str_replace('/', '_', $menu->permission) . '_' . $menu->id
                : ($menu->model ? $menu->model . '_' . $menu->id : 'menu_' . $menu->id);

            $item = [
                'name' => $routeName,
                'path' => $menu->url ?? '',
                'component' => $component,
                'meta' => [
                    'type' => 'M',
                    'icon' => $menu->icon ?? '',
                    'title' => $menu->name,
                ],
            ];

            $subChildren = $this->buildFrontendTree($menus, $menu->id);
            if (!empty($subChildren)) {
                $item['children'] = $subChildren;
            }

            $tree[] = $item;
        }

        return $tree;
    }

    /**
     * 获取用户菜单
     */
    public function getUserMenus($userMenus, $appId = 0)
    {
        $query = $this->query()
            ->whereIn('id', $userMenus)
            ->where('is_show', 1)
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取菜单统计
     */
    public function getMenuStats($appId = 0)
    {
        $base = $this->query();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total' => (clone $base)->count(),
            'show'  => (clone $base)->where('is_show', 1)->count(),
            'hide'  => (clone $base)->where('is_show', 0)->count(),
        ];
    }

    /**
     * 子菜单数
     */
    public function countChildren(int $parentId): int
    {
        return $this->query()->where('parent_id', $parentId)->count();
    }
}
