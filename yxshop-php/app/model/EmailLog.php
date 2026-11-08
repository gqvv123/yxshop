<?php

namespace app\model;

/**
 * 邮件日志模型
 */
class EmailLog extends BaseModel
{
    protected $table = 'yxshop_email_logs';
    protected $primaryKey = 'id';

    protected $fillable = [
        'email',
        'subject',
        'content',
        'template_id',
        'template_code',
        'send_status',
        'send_result',
        'send_time',
        'user_id',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'send_status' => 'integer',
        'send_result' => 'array',
        'send_time' => 'integer',
        'user_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 发送状态常量
    const STATUS_PENDING = 0;    // 待发送
    const STATUS_SUCCESS = 1;    // 发送成功
    const STATUS_FAILED = 2;     // 发送失败

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
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 记录邮件发送
     */
    public static function record($email, $subject, $content, $templateId = 0, $templateCode = '', $userId = 0, $appId = 0)
    {
        return static::create([
            'email' => $email,
            'subject' => $subject,
            'content' => $content,
            'template_id' => $templateId,
            'template_code' => $templateCode,
            'send_status' => self::STATUS_PENDING,
            'user_id' => $userId,
            'app_id' => $appId,
        ]);
    }
}
