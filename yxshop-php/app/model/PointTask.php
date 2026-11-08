<?php

namespace app\model;

/**
 * 积分任务模型
 *
 * 定义可获取积分的任务：签到、完善资料、首次下单、评价商品等
 */
class PointTask extends BaseModel
{
    protected $table = 'yxshop_point_tasks';

    protected $usesSnowflake = false;
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // 任务类型
    const TYPE_DAILY     = 1;  // 每日任务
    const TYPE_ONCE      = 2;  // 一次性任务
    const TYPE_WEEKLY    = 3;  // 每周任务

    // 任务编码（唯一标识）
    const CODE_SIGN          = 'sign';
    const CODE_PROFILE       = 'complete_profile';
    const CODE_FIRST_ORDER   = 'first_order';
    const CODE_REVIEW        = 'first_review';
    const CODE_SHARE         = 'share_product';

    protected $fillable = [
        'name', 'code', 'type', 'reward_points', 'condition_desc',
        'icon', 'sort', 'status', 'app_id', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'type'           => 'integer',
        'reward_points'  => 'integer',
        'sort'           => 'integer',
        'status'         => 'integer',
        'app_id'         => 'integer',
        'created_at'     => 'integer',
        'updated_at'     => 'integer',
    ];

    public function userTasks()
    {
        return $this->hasMany(UserPointTask::class, 'task_id', 'id');
    }
}
