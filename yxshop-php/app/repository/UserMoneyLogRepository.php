<?php

namespace app\repository;

use app\model\UserMoneyLog;

class UserMoneyLogRepository extends BaseRepository
{
    protected $model = UserMoneyLog::class;

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $query = $this->query()->with('user');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('id', 'desc')->paginate($pageSize);
    }
}
