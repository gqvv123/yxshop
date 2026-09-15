<?php

namespace app\model;

class ScheduledTask extends BaseModel
{
    protected $table = 'yxshop_scheduled_tasks';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    const RUN_STATUS_FAILED = 0;
    const RUN_STATUS_SUCCESS = 1;

    const TASK_TYPE_COMMAND = 'command';
    const TASK_TYPE_CLOSURE = 'closure';
    const TASK_TYPE_URL = 'url';

    protected $fillable = [
        'task_name', 'task_type', 'cron_expression', 'task_command',
        'status', 'last_run_at', 'last_run_status', 'app_id'
    ];

    protected $casts = [
        'status' => 'integer',
        'last_run_at' => 'integer',
        'last_run_status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function logs()
    {
        return $this->hasMany(ScheduledTaskLog::class, 'task_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}