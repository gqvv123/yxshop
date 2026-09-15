<?php

namespace app\observer;

use app\model\Promotion;

class PromotionObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return Promotion::class;
    }
}
