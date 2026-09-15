<?php

namespace app\repository;

use app\model\ContentPage;

class ContentPageRepository extends BaseRepository
{
    protected $model = ContentPage::class;

    public function paginateForList(int $appId = 0, int $pageSize = 20, string $keyword = '', string $pageType = '')
    {
        $query = $this->model->newQuery()->where('deleted_at', 0)->orderBy('sort', 'asc')->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($keyword !== '') {
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        if ($pageType !== '') {
            $query->where('page_type', $pageType);
        }

        return $query->paginate($pageSize);
    }
}