<?php

namespace app\repository;

use app\model\MiniTheme;

class MiniThemeRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new MiniTheme());
    }

    public function getThemesByApp($appId)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function getActiveTheme($appId)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('is_active', 1)
            ->first();
    }
}