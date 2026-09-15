<?php

namespace app\observer;

use app\model\Admin;

class AdminObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return Admin::class;
    }
}
