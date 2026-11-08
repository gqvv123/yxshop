<?php

namespace app\repository;

use app\model\UploadGroup;

class UploadGroupRepository extends BaseRepository
{
    protected $model = UploadGroup::class;

    public function getGroups($appId = 0)
    {
        $query = $this->model->newQuery()
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 按 app 列表（按 sort asc）
     */
    public function listGroups(int $appId = 0)
    {
        $query = $this->model->newQuery();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }
}
