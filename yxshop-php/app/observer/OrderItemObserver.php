<?php

namespace app\observer;

use app\model\OrderItem;

/**
 * 组件：Order Item Observer。
 *
 * @package app\observer
 */
class OrderItemObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return OrderItem::class;
    }
}
