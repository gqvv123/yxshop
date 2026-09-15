<?php

namespace app\repository;

use app\model\AfterSalesImage;

class AfterSalesImageRepository extends BaseRepository
{
    protected $model = AfterSalesImage::class;

    public function getByAfterSalesId(int $afterSalesId)
    {
        return $this->query()
            ->where('after_sales_id', $afterSalesId)
            ->orderBy('id', 'asc')
            ->get();
    }
}
