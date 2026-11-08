<?php

namespace app\observer;

use app\model\AfterSales;

/**
 * 组件：After Sales Observer。
 *
 * @package app\observer
 */
class AfterSalesObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return AfterSales::class;
    }
}
