<?php

namespace app\repository;

use app\model\AppPrepayId;

class AppPrepayIdRepository extends BaseRepository
{
    protected $model = AppPrepayId::class;

    public function getPages($appId = 0)
    {
        $query = $this->query()->orderBy('sort', 'asc')->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->get();
    }
}


