<?php

namespace app\model;

/**
 * 通知模板模型
 */
class NotificationTemplate extends BaseModel
{
    protected $table = 'yxshop_notification_templates';
    protected $primaryKey = 'id';

    protected $fillable = [
        'template_name',
        'template_type',
        'template_title',
        'template_content',
        'template_variables',
        'is_active',
        'sort',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'template_variables' => 'array',
        'is_active' => 'integer',
        'sort' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 模板类型常量
    const TYPE_SMS = 'sms';           // 短信
    const TYPE_EMAIL = 'email';       // 邮件
    const TYPE_WECHAT = 'wechat';     // 微信
    const TYPE_PUSH = 'push';         // 推送
    const TYPE_SYSTEM = 'system';     // 系统通知

    /**
     * 获取模板类型文本
     */
    public function getTemplateTypeTextAttribute()
    {
        $types = [
            self::TYPE_SMS => '短信',
            self::TYPE_EMAIL => '邮件',
            self::TYPE_WECHAT => '微信',
            self::TYPE_PUSH => '推送',
            self::TYPE_SYSTEM => '系统通知',
        ];

        return $types[$this->template_type] ?? $this->template_type;
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 关联场景模板
     */
    public function sceneTemplates()
    {
        return $this->hasMany(NotificationSceneTemplate::class, 'template_id', 'id');
    }

    /**
     * 关联发送记录
     */
    public function sends()
    {
        return $this->hasMany(NotificationSend::class, 'template_id', 'id');
    }

    /**
     * 渲染模板内容
     */
    public function renderContent($variables = [])
    {
        $content = $this->template_content;
        
        foreach ($variables as $key => $value) {
            $content = str_replace('{{' . $key . '}}', $value, $content);
        }
        
        return $content;
    }

    /**
     * 渲染模板标题
     */
    public function renderTitle($variables = [])
    {
        $title = $this->template_title;
        
        foreach ($variables as $key => $value) {
            $title = str_replace('{{' . $key . '}}', $value, $title);
        }
        
        return $title;
    }
}
