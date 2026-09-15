<?php

namespace app\repository;

use app\model\PaymentLog;

class PaymentLogRepository extends BaseRepository
{
    protected $model = PaymentLog::class;

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $query = $this->query()->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($keyword !== '') {
            $query->where('order_no', 'like', "%{$keyword}%");
        }

        return $query->paginate($pageSize);
    }
}
