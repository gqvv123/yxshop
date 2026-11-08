<?php

namespace app\model;

/**
 * 通知队列模型
 */
class NotificationQueue extends BaseModel
{
    protected $table = 'yxshop_notification_queues';

    protected $fillable = [
        'scene_id',
        'template_id',
        'user_id',
        'user_type',
        'send_type',
        'send_title',
        'send_content',
        'send_to',
        'send_data',
        'send_priority',
        'send_status',
        'send_attempts',
        'send_max_attempts',
        'send_next_time',
        'send_result',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'scene_id' => 'integer',
        'template_id' => 'integer',
        'user_id' => 'integer',
        'user_type' => 'integer',
        'send_priority' => 'integer',
        'send_status' => 'integer',
        'send_attempts' => 'integer',
        'send_max_attempts' => 'integer',
        'send_next_time' => 'integer',
        'send_data' => 'array',
        'send_result' => 'array',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 用户类型常量
    const USER_TYPE_USER = 1;    // 用户
    const USER_TYPE_ADMIN = 2;   // 管理员

    // 发送类型常量
    const SEND_TYPE_SMS = 'sms';         // 短信
    const SEND_TYPE_EMAIL = 'email';     // 邮件
    const SEND_TYPE_WECHAT = 'wechat';   // 微信
    const SEND_TYPE_PUSH = 'push';       // 推送
    const SEND_TYPE_SYSTEM = 'system';   // 系统通知

    // 发送状态常量
    const STATUS_PENDING = 0;    // 待发送
    const STATUS_PROCESSING = 1; // 发送中
    const STATUS_SUCCESS = 2;    // 发送成功
    const STATUS_FAILED = 3;     // 发送失败
    const STATUS_CANCELLED = 4;  // 已取消

    // 优先级常量
    const PRIORITY_LOW = 1;      // 低优先级
    const PRIORITY_NORMAL = 2;   // 普通优先级
    const PRIORITY_HIGH = 3;     // 高优先级
    const PRIORITY_URGENT = 4;   // 紧急优先级

    /**
     * 获取用户类型文本
     */
    public function getUserTypeTextAttribute()
    {
        $types = [
            self::USER_TYPE_USER => '用户',
            self::USER_TYPE_ADMIN => '管理员',
        ];

        return $types[$this->user_type] ?? '未知';
    }

    /**
     * 获取发送类型文本
     */
    public function getSendTypeTextAttribute()
    {
        $types = [
            self::SEND_TYPE_SMS => '短信',
            self::SEND_TYPE_EMAIL => '邮件',
            self::SEND_TYPE_WECHAT => '微信',
            self::SEND_TYPE_PUSH => '推送',
            self::SEND_TYPE_SYSTEM => '系统通知',
        ];

        return $types[$this->send_type] ?? $this->send_type;
    }

    /**
     * 获取发送状态文本
     */
    public function getSendStatusTextAttribute()
    {
        $statuses = [
            self::STATUS_PENDING => '待发送',
            self::STATUS_PROCESSING => '发送中',
            self::STATUS_SUCCESS => '发送成功',
            self::STATUS_FAILED => '发送失败',
            self::STATUS_CANCELLED => '已取消',
        ];

        return $statuses[$this->send_status] ?? '未知';
    }

    /**
     * 获取优先级文本
     */
    public function getSendPriorityTextAttribute()
    {
        $priorities = [
            self::PRIORITY_LOW => '低优先级',
            self::PRIORITY_NORMAL => '普通优先级',
            self::PRIORITY_HIGH => '高优先级',
            self::PRIORITY_URGENT => '紧急优先级',
        ];

        return $priorities[$this->send_priority] ?? '未知';
    }

    /**
     * 关联场景
     */
    public function scene()
    {
        return $this->belongsTo(NotificationScene::class, 'scene_id', 'id');
    }

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id', 'id');
    }

    /**
     * 关联用户
     */
    public function user()
    {
        if ($this->user_type == self::USER_TYPE_USER) {
            return $this->belongsTo(User::class, 'user_id', 'id');
        } else {
            return $this->belongsTo(Admin::class, 'user_id', 'id');
        }
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
