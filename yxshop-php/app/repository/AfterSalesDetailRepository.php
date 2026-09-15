<?php

namespace app\repository;

use app\model\AfterSalesDetail;

class AfterSalesDetailRepository extends BaseRepository
{
    protected $model = AfterSalesDetail::class;

    /**
     * 获取售后沟通流（按时间正序，便于聊天式渲染）
     */
    public function getThreadByAfterSalesId(int $afterSalesId)
    {
        return $this->query()
            ->where('after_sales_id', $afterSalesId)
            ->orderBy('created_at', 'asc')
            ->get();
    }
}
