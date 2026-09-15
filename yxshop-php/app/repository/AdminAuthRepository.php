<?php

namespace app\repository;

use app\model\AdminMenu;

class AdminAuthRepository extends BaseRepository
{
    protected $model = AdminMenu::class;

    public function getAuthTree($parentId = 0, $appId = 0)
    {
        $query = $this->query()
            ->where('parent_id', $parentId)
            ->where('is_show', 1)
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        $auths = $query->get();

        foreach ($auths as $auth) {
            $auth->children = $this->getAuthTree($auth->id, $appId);
        }

        return $auths;
    }

    /**
     * 权限统计（每项 clone 防累加 where）
     */
    public function getAuthStats($appId = 0)
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
     * 子权限数
     */
    public function countChildren(int $parentId): int
    {
        return $this->query()->where('parent_id', $parentId)->count();
    }

    /**
     * 角色拥有的权限 ID 列表（通过 yxshop_admin_role_auths 中间表）
     * @return array<int>
     */
    public function getRoleAuths($roleId, $appId = 0): array
    {
        $ids = \support\Db::table('yxshop_admin_role_auths')
            ->where('role_id', $roleId)
            ->pluck('auth_id')
            ->toArray();
        return array_map('intval', $ids);
    }
}
