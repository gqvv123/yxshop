<?php

namespace app\repository;

use app\model\AppPage;

class AppPageRepository extends BaseRepository
{
    protected $model = AppPage::class;

    public function getPages($appId = 0)
    {
        $query = $this->model->newQuery()->orderBy('sort', 'asc')->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->get();
    }

    /**
     * 按 app 维度的 status 统计
     */
    public function getPageStats($appId = 0): array
    {
        $base = $this->model->newQuery();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total'    => (clone $base)->count(),
            'active'   => (clone $base)->where('status', 1)->count(),
            'inactive' => (clone $base)->where('status', 0)->count(),
        ];
    }
}


