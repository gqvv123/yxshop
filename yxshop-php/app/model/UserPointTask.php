<?php

namespace app\model;

/**
 * 用户积分任务完成记录
 */
class UserPointTask extends BaseModel
{
    protected $table = 'yxshop_user_point_tasks';

    protected $usesSnowflake = false;
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // 状态
    const STATUS_COMPLETED  = 1;  // 已完成
    const STATUS_CLAIMED    = 2;  // 已领取奖励

    protected $fillable = [
        'user_id', 'task_id', 'status', 'completed_at', 'claimed_at', 'app_id', 'created_at'
    ];

    protected $casts = [
        'user_id'      => 'integer',
        'task_id'      => 'integer',
        'status'       => 'integer',
        'completed_at' => 'integer',
        'claimed_at'   => 'integer',
        'app_id'       => 'integer',
        'created_at'   => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function task()
    {
        return $this->belongsTo(PointTask::class, 'task_id', 'id');
    }
}
