<?php

namespace app\model;

/**
 * 通知发送记录模型
 */
class NotificationSend extends BaseModel
{
    protected $table = 'yxshop_notification_sends';
    protected $primaryKey = 'id';

    protected $fillable = [
        'scene_id',
        'template_id',
        'user_id',
        'user_type',
        'send_type',
        'send_title',
        'send_content',
        'send_to',
        'send_status',
        'send_result',
        'send_time',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'scene_id' => 'integer',
        'template_id' => 'integer',
        'user_id' => 'integer',
        'user_type' => 'integer',
        'send_status' => 'integer',
        'send_result' => 'array',
        'send_time' => 'integer',
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
    const STATUS_SUCCESS = 1;    // 发送成功
    const STATUS_FAILED = 2;     // 发送失败

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
            self::STATUS_SUCCESS => '发送成功',
            self::STATUS_FAILED => '发送失败',
        ];

        return $statuses[$this->send_status] ?? '未知';
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