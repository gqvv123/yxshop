<?php

namespace app\observer;

use app\model\AfterSales;

class AfterSalesObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return AfterSales::class;
    }
}
