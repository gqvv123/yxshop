<?php

namespace app\repository;

use app\model\Admin;

/**
 * 管理员仓储类
 */
class AdminRepository extends BaseRepository
{
    protected $model = Admin::class;

    /**
     * 根据用户名查找管理员
     */
    public function findByUserName(string $userName): ?Admin
    {
        return $this->query()
            ->where('username', $userName)
            ->first();
    }

    /**
     * 获取管理员列表
     */
    public function getAdmins(int $appId = 0, ?int $roleId = null)
    {
        $query = $this->query()
            ->with(['role', 'app'])
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($roleId !== null) {
            $query->where('role_id', $roleId);
        }

        return $query->get();
    }

    /**
     * 获取超级管理员
     */
    public function getSuperAdmins(int $appId = 0)
    {
        $query = $this->query()
            ->where('is_super_admin', 1);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取管理员统计
     * @return array{total:int, super:int, normal:int, active:int}
     */
    public function getAdminStats(int $appId = 0): array
    {
        $base = $this->query();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total' => (clone $base)->count(),
            'super' => (clone $base)->where('is_super_admin', 1)->count(),
            'normal' => (clone $base)->where('is_super_admin', 0)->count(),
            'active' => (clone $base)->count(),
        ];
    }

    /**
     * 搜索管理员
     */
    public function searchAdmins(string $keyword, int $appId = 0)
    {
        $query = $this->query()
            ->where(function ($q) use ($keyword) {
                $q->where('username', 'like', '%' . $keyword . '%')
                  ->orWhere('nickname', 'like', '%' . $keyword . '%')
                  ->orWhere('phone', 'like', '%' . $keyword . '%');
            })
            ->with(['role', 'app'])
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }
}
