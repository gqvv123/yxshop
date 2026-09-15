<?php

namespace app\model;

/**
 * 通知配置模型
 */
class NotificationConfig extends BaseModel
{
    protected $table = 'yxshop_notification_configs';

    protected $fillable = [
        'config_type',
        'config_key',
        'config_value',
        'config_desc',
        'is_active',
        'sort',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'integer',
        'sort' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 配置类型常量
    const TYPE_SMS = 'sms';           // 短信配置
    const TYPE_EMAIL = 'email';       // 邮件配置
    const TYPE_WECHAT = 'wechat';     // 微信配置
    const TYPE_PUSH = 'push';         // 推送配置
    const TYPE_SYSTEM = 'system';     // 系统配置

    /**
     * 获取配置类型文本
     */
    public function getConfigTypeTextAttribute()
    {
        $types = [
            self::TYPE_SMS => '短信配置',
            self::TYPE_EMAIL => '邮件配置',
            self::TYPE_WECHAT => '微信配置',
            self::TYPE_PUSH => '推送配置',
            self::TYPE_SYSTEM => '系统配置',
        ];

        return $types[$this->config_type] ?? $this->config_type;
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 获取配置值（自动解析JSON）
     */
    public function getConfigValueAttribute($value)
    {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }
        return $value;
    }

    /**
     * 设置配置值（自动编码JSON）
     */
    public function setConfigValueAttribute($value)
    {
        if (is_array($value) || is_object($value)) {
            $this->attributes['config_value'] = json_encode($value);
        } else {
            $this->attributes['config_value'] = $value;
        }
    }
}