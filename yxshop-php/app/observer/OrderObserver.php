<?php

namespace app\observer;

use app\model\Order;

/**
 * 组件：Order Observer。
 *
 * @package app\observer
 */
class OrderObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return Order::class;
    }
}
