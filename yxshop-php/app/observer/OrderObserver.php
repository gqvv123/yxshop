<?php

namespace app\observer;

use app\model\Order;

class OrderObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return Order::class;
    }
}
