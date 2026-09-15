<?php

namespace app\observer;

use app\model\OrderItem;

class OrderItemObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return OrderItem::class;
    }
}
