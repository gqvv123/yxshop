<?php

namespace app\observer;

use app\model\Setting;

class SettingObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return Setting::class;
    }
}
