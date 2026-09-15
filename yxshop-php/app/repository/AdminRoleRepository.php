<?php

namespace app\repository;

use app\model\AdminRole;

class AdminRoleRepository extends BaseRepository
{
    protected $model = AdminRole::class;

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
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
        ];
    }
}
