<?php

namespace app\observer;

use app\model\Admin;

/**
 * 组件：Admin Observer。
 *
 * @package app\observer
 */
class AdminObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return Admin::class;
    }
}
