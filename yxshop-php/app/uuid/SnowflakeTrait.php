<?php

namespace app\uuid;

trait SnowflakeTrait
{
    protected static function bootSnowflakeTrait(): void
    {
        static::creating(function ($model) {
            // 允许日志/流水等自增表关闭 Snowflake：protected $usesSnowflake = false;
            if (property_exists($model, 'usesSnowflake') && $model->usesSnowflake === false) {
                return;
            }
            if (empty($model->id)) {
                $model->id = Snowflake::generate();
            }
        });
    }
}
