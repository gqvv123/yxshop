<?php

namespace app\observer;

use app\model\Item;

class ItemObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return Item::class;
    }
}
