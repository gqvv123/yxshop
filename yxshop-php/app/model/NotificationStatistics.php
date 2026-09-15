<?php

namespace app\model;

/**
 * 通知统计模型
 */
class NotificationStatistics extends BaseModel
{
    protected $table = 'yxshop_notification_statistics';
    protected $primaryKey = 'id';

    protected $fillable = [
        'statistics_date',
        'scene_id',
        'template_id',
        'send_type',
        'total_count',
        'success_count',
        'failed_count',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'scene_id' => 'integer',
        'template_id' => 'integer',
        'total_count' => 'integer',
        'success_count' => 'integer',
        'failed_count' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 发送类型常量
    const SEND_TYPE_SMS = 'sms';         // 短信
    const SEND_TYPE_EMAIL = 'email';     // 邮件
    const SEND_TYPE_WECHAT = 'wechat';   // 微信
    const SEND_TYPE_PUSH = 'push';       // 推送
    const SEND_TYPE_SYSTEM = 'system';   // 系统通知

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
     * 获取成功率
     */
    public function getSuccessRateAttribute()
    {
        if ($this->total_count == 0) {
            return 0;
        }
        return round(($this->success_count / $this->total_count) * 100, 2);
    }

    /**
     * 获取失败率
     */
    public function getFailureRateAttribute()
    {
        if ($this->total_count == 0) {
            return 0;
        }
        return round(($this->failed_count / $this->total_count) * 100, 2);
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
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
