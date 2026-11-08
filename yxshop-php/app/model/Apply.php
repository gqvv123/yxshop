<?php

namespace app\model;

/**
 * 申请模型
 */
class Apply extends BaseModel
{
    protected $table = 'yxshop_applies';

    protected $fillable = [
        'user_id',
        'apply_type',
        'apply_title',
        'apply_content',
        'apply_data',
        'apply_status',
        'apply_result',
        'apply_time',
        'audit_time',
        'audit_user',
        'audit_remark',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'apply_type' => 'integer',
        'apply_data' => 'array',
        'apply_status' => 'integer',
        'apply_result' => 'array',
        'apply_time' => 'integer',
        'audit_time' => 'integer',
        'audit_user' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 申请类型常量
    const TYPE_AGENT = 1;          // 代理申请
    const TYPE_REFUND = 2;         // 退款申请
    const TYPE_RETURN = 3;         // 退货申请
    const TYPE_EXCHANGE = 4;       // 换货申请
    const TYPE_COMPLAINT = 5;      // 投诉申请
    const TYPE_SUGGESTION = 6;     // 建议申请

    // 申请状态常量
    const STATUS_PENDING = 0;      // 待审核
    const STATUS_APPROVED = 1;     // 已通过
    const STATUS_REJECTED = 2;     // 已拒绝
    const STATUS_PROCESSING = 3;   // 处理中
    const STATUS_COMPLETED = 4;    // 已完成
    const STATUS_CANCELLED = 5;    // 已取消

    /**
     * 获取申请类型文本
     */
    public function getApplyTypeTextAttribute()
    {
        $types = [
            self::TYPE_AGENT => '代理申请',
            self::TYPE_REFUND => '退款申请',
            self::TYPE_RETURN => '退货申请',
            self::TYPE_EXCHANGE => '换货申请',
            self::TYPE_COMPLAINT => '投诉申请',
            self::TYPE_SUGGESTION => '建议申请',
        ];

        return $types[$this->apply_type] ?? '未知';
    }

    /**
     * 获取申请状态文本
     */
    public function getApplyStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => '待审核',
            self::STATUS_APPROVED => '已通过',
            self::STATUS_REJECTED => '已拒绝',
            self::STATUS_PROCESSING => '处理中',
            self::STATUS_COMPLETED => '已完成',
            self::STATUS_CANCELLED => '已取消',
        ];

        return $statuses[$this->apply_status] ?? '未知';
    }

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联审核用户
     */
    public function auditor()
    {
        return $this->belongsTo(Admin::class, 'audit_user', 'id');
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
