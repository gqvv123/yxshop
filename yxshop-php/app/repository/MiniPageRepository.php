<?php

namespace app\repository;

use app\model\MiniPage;

class MiniPageRepository extends BaseRepository
{
    public function __construct()
    {
        parent::__construct(new MiniPage());
    }

    public function getPagesByApp($appId, $pageType = null, $status = null, $pageSize = 20)
    {
        $query = $this->query()->where('app_id', $appId);

        if ($pageType !== null) {
            $query->where('page_type', $pageType);
        }
        if ($status !== null) {
            $query->where('status', $status);
        }

        return $query->orderBy('updated_at', 'desc')->paginate($pageSize);
    }

    public function getPublishedHomePage($appId)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('page_type', MiniPage::TYPE_HOME)
            ->where('status', MiniPage::STATUS_PUBLISHED)
            ->first();
    }

    public function countByStatus($appId, $status)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', $status)
            ->count();
    }
}