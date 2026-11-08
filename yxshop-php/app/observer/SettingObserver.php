<?php

namespace app\observer;

use app\model\Setting;

/**
 * 组件：Setting Observer。
 *
 * @package app\observer
 */
class SettingObserver extends BaseObserver
{
    protected function getModelClass(): string
    {
        return Setting::class;
    }
}
