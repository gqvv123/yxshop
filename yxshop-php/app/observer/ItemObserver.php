<?php

namespace app\observer;

use app\model\Item;

/**
 * 组件：Item Observer。
 *
 * @package app\observer
 */
class ItemObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return Item::class;
    }
}
