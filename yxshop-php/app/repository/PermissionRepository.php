<?php

namespace app\repository;

use app\model\Permission;

/**
 * 权限仓储
 */
class PermissionRepository extends BaseRepository
{
    protected $model = Permission::class;

    /**
     * 获取已启用的权限
     */
    public function getEnabled(int $appId = 0)
    {
        $query = $this->query()->where('status', Permission::STATUS_ENABLED);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }

    /**
     * 按类型获取权限
     */
    public function getByType(int $permissionType, int $appId = 0)
    {
        $query = $this->query()->where('permission_type', $permissionType);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }

    /**
     * 按父级获取权限
     */
    public function getByParent(int $parentId, int $appId = 0)
    {
        $query = $this->query()->where('parent_id', $parentId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }

    /**
     * 获取顶级权限
     */
    public function getRootPermissions(int $appId = 0)
    {
        return $this->getByParent(0, $appId);
    }

    /**
     * 按菜单获取权限
     */
    public function getByMenu(int $menuId, int $appId = 0)
    {
        $query = $this->query()->where('menu_id', $menuId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('sort', 'asc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['permission_type']) && $filters['permission_type'] !== '') {
            $query->where('permission_type', $filters['permission_type']);
        }
        if (!empty($filters['keyword'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['keyword']);
            $query->where('permission_name', 'like', '%' . $escaped . '%');
        }
        return $query->with(['parent', 'menu'])->paginate($pageSize);
    }

    /**
     * 获取权限树
     */
    public function getPermissionTree(int $appId = 0): array
    {
        $permissions = $this->query()->where('app_id', $appId)->orderBy('sort', 'asc')->get();
        return $this->buildTree($permissions->toArray(), 0);
    }

    /**
     * 构建树形结构
     */
    private function buildTree(array $permissions, int $parentId): array
    {
        $tree = [];
        foreach ($permissions as $permission) {
            if ($permission['parent_id'] == $parentId) {
                $children = $this->buildTree($permissions, $permission['id']);
                if (!empty($children)) {
                    $permission['children'] = $children;
                }
                $tree[] = $permission;
            }
        }
        return $tree;
    }
}