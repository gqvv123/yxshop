<?php

namespace app\observer;

use app\model\User;

/**
 * 组件：User Observer。
 *
 * @package app\observer
 */
class UserObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return User::class;
    }
}
