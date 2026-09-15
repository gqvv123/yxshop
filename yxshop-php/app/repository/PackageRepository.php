<?php

namespace app\repository;

use app\model\Package;

/**
 * 套餐仓储类
 */
class PackageRepository extends BaseRepository
{
    protected $model = Package::class;

    /**
     * 按 app 获取套餐列表（按 sort 升序）
     */
    public function getPackages(int $appId = 0): array
    {
        $query = $this->model->newQuery();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('sort', 'asc')->get()->toArray();
    }

    /**
     * 按 app 获取生效中的套餐列表
     */
    public function getActivePackages(int $appId = 0): array
    {
        $now = time();
        $query = $this->model->newQuery()
            ->where('status', 1)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('sort', 'asc')->get()->toArray();
    }
}
