<?php

namespace app\repository;

use app\model\MiniTabBar;

class MiniTabBarRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new MiniTabBar());
    }

    public function getByApp($appId)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->first();
    }
}