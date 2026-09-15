<?php

namespace app\repository;

use app\model\AdminMenu;

class AuthRepository extends BaseRepository
{
    protected $model = AdminMenu::class;

    public function getTreeByAppId(int $appId = 0)
    {
        $query = $this->query()->where('is_show', 1);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }
}
