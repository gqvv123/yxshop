<?php

namespace app\repository;

use app\model\Delivery;

class DeliveryRepository extends BaseRepository
{
    protected $model = Delivery::class;

    public function getDeliveries($appId = 0)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) $query->where('app_id', $appId);
        return $query->get();
    }

    public function paginateForList(int $appId = 0, int $pageSize = 20, string $keyword = '')
    {
        $query = $this->model->newQuery()
            ->withCount('rules')
            ->orderBy('created_at', 'desc');

        if ($appId > 0) $query->where('app_id', $appId);
        if ($keyword !== '') $query->where('name', 'like', '%' . $keyword . '%');

        return $query->paginate($pageSize);
    }

    public function getDeliveryStats($appId = 0)
    {
        $base = $this->model->newQuery();
        if ($appId > 0) $base->where('app_id', $appId);
        return ['total' => (clone $base)->count()];
    }
}
