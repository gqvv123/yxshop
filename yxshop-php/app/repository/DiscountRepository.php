<?php

namespace app\repository;

use app\model\Promotion;

class DiscountRepository extends BaseRepository
{
    protected $model = Promotion::class;

    public function getActiveDiscounts($appId)
    {
        $now = time();
        $query = $this->query()
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->where('is_show', 1)
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    public function getByAppId($appId)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 后台分页（可选 app 过滤）
     */
    public function getPaginatedByApp(int $appId = 0, int $page = 1, int $pageSize = 20)
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }
}
