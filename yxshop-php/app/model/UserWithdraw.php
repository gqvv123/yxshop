<?php

namespace app\model;

/**
 * 用户提现申请模型
 *
 * 复用 AgentWithdraw 的字段结构，但独立表以区分 C 端用户提现
 */
class UserWithdraw extends BaseModel
{
    protected $table = 'yxshop_user_withdraws';

    protected $usesSnowflake = false;
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    // 提现类型
    const TYPE_BANK    = 1;
    const TYPE_ALIPAY  = 2;
    const TYPE_WECHAT  = 3;

    // 提现状态
    const STATUS_PENDING    = 0;
    const STATUS_APPROVED   = 1;
    const STATUS_REJECTED   = 2;
    const STATUS_SUCCESS    = 3;
    const STATUS_FAILED     = 4;

    protected $fillable = [
        'user_id', 'amount', 'fee', 'actual_amount', 'withdraw_type',
        'account', 'account_name', 'bank_name', 'bank_branch',
        'status', 'remark', 'process_desc', 'process_time',
        'app_id', 'created_at', 'updated_at'
    ];

    protected $casts = [
        'user_id'       => 'integer',
        'amount'        => 'decimal:2',
        'fee'           => 'decimal:2',
        'actual_amount' => 'decimal:2',
        'withdraw_type' => 'integer',
        'status'        => 'integer',
        'process_time'  => 'integer',
        'app_id'        => 'integer',
        'created_at'    => 'integer',
        'updated_at'    => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function getStatusTextAttribute()
    {
        $map = [
            self::STATUS_PENDING  => '审核中',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_SUCCESS  => '提现成功',
            self::STATUS_FAILED   => '提现失败',
        ];
        return $map[$this->status] ?? '未知';
    }
}
