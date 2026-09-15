<?php

namespace app\model;

class ScheduledTaskLog extends BaseModel
{
    protected $table = 'yxshop_scheduled_task_logs';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    const STATUS_FAILED = 0;
    const STATUS_SUCCESS = 1;

    protected $fillable = [
        'task_id', 'started_at', 'finished_at', 'status', 'result',
        'error_message', 'app_id'
    ];

    protected $casts = [
        'task_id' => 'integer',
        'started_at' => 'integer',
        'finished_at' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function task()
    {
        return $this->belongsTo(ScheduledTask::class, 'task_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}