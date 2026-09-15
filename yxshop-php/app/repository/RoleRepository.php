<?php

namespace app\repository;

use app\model\AdminRole;
use app\model\AdminMenu;

class RoleRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new AdminRole());
    }

    public function getRoles(int $appId = 0)
    {
        $query = $this->query()
            ->withCount('admins')
            ->orderBy('id', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    public function findWithAuths($id)
    {
        return $this->query()->with('auths')->find((string) $id);
    }

    public function findByRoleName(string $roleName, int $appId = 0)
    {
        $query = $this->query()
            ->where('role_name', $roleName);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->first();
    }

    public function findByRoleNameExclude(string $roleName, string $excludeId, int $appId = 0)
    {
        $query = $this->query()
            ->where('role_name', $roleName)
            ->where('id', '!=', $excludeId);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->first();
    }

    public function getAdminCount(string $roleId): int
    {
        $role = $this->query()->find((string) $roleId);
        if (!$role) {
            return 0;
        }
        // Admin 模型全局 scope 已自动过滤 deleted_at=0
        return $role->admins()->count();
    }

    public function syncAuths(string $roleId, array $authIds): void
    {
        $role = $this->query()->find((string) $roleId);
        if ($role) {
            $role->auths()->sync($authIds);
        }
    }

    public function detachAuths(string $roleId): void
    {
        $role = $this->query()->find((string) $roleId);
        if ($role) {
            $role->auths()->detach();
        }
    }

    public function getAuthIds(string $roleId): array
    {
        $role = $this->query()->find((string) $roleId);
        if (!$role) {
            return [];
        }
        return $role->getAuthIds();
    }

    public function getAllMenus()
    {
        return AdminMenu::where('is_show', 1)
            ->orderBy('sort', 'asc')
            ->get();
    }

    public function searchRoles(string $keyword, int $appId = 0)
    {
        $query = $this->query()
            ->where(function ($q) use ($keyword) {
                $q->where('role_name', 'like', '%' . $keyword . '%')
                  ->orWhere('role_desc', 'like', '%' . $keyword . '%');
            })
            ->withCount('admins')
            ->orderBy('id', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    public function getRoleStats(int $appId = 0): array
    {
        $base = $this->model->newQuery();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total' => (clone $base)->count(),
        ];
    }

    /**
     * 角色树（按 app）
     */
    public function getRoleTree(int $appId = 0)
    {
        $query = $this->model->newQuery();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('id', 'asc')->get();
    }
}
