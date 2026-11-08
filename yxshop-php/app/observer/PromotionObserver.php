<?php

namespace app\observer;

use app\model\Promotion;

/**
 * 组件：Promotion Observer。
 *
 * @package app\observer
 */
class PromotionObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return Promotion::class;
    }
}
