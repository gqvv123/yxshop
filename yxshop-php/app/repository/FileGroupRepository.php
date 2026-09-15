<?php

namespace app\repository;

use app\model\UploadGroup;

class FileGroupRepository extends BaseRepository
{
    protected $model = UploadGroup::class;

    public function getByAppId($appId)
    {
        return $this->model->newQuery()
            ->where('app_id', $appId)
            ->orderBy('sort', 'asc')
            ->get();
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
