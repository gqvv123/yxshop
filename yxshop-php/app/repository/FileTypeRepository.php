<?php

namespace app\repository;

use app\model\UploadType;

class FileTypeRepository extends BaseRepository
{
    protected $model = UploadType::class;

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
    public function listTypes(int $appId = 0)
    {
        $query = $this->model->newQuery();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }

    /**
     * 同 app 下按 code 查找（可排除 id）
     */
    public function findByCode(string $code, int $appId, ?int $excludeId = null)
    {
        $query = $this->model->newQuery()
            ->where('code', $code)
            ->where('app_id', $appId);
        if ($excludeId !== null) {
            $query->where('id', '<>', $excludeId);
        }
        return $query->first();
    }
}
