<?php

namespace app\model;

/**
 * 通知黑名单模型
 */
class NotificationBlacklist extends BaseModel
{
    protected $table = 'yxshop_notification_blacklists';

    protected $fillable = [
        'blacklist_type',
        'blacklist_value',
        'blacklist_reason',
        'is_active',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_active' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 黑名单类型常量
    const TYPE_PHONE = 'phone';       // 手机号
    const TYPE_EMAIL = 'email';       // 邮箱
    const TYPE_WECHAT = 'wechat';     // 微信
    const TYPE_IP = 'ip';             // IP地址
    const TYPE_USER = 'user';         // 用户ID

    /**
     * 获取黑名单类型文本
     */
    public function getBlacklistTypeTextAttribute()
    {
        $types = [
            self::TYPE_PHONE => '手机号',
            self::TYPE_EMAIL => '邮箱',
            self::TYPE_WECHAT => '微信',
            self::TYPE_IP => 'IP地址',
            self::TYPE_USER => '用户ID',
        ];

        return $types[$this->blacklist_type] ?? $this->blacklist_type;
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 检查是否在黑名单中
     */
    public static function isBlacklisted($type, $value, $appId = 0)
    {
        $query = static::where('blacklist_type', $type)
            ->where('blacklist_value', $value)
            ->where('is_active', true);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->exists();
    }
}
