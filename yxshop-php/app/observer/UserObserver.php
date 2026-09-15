<?php

namespace app\observer;

use app\model\User;

class UserObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return User::class;
    }
}
