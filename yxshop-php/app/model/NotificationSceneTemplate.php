<?php

namespace app\model;

/**
 * 通知场景模板关联模型
 */
class NotificationSceneTemplate extends BaseModel
{
    protected $table = 'yxshop_notification_scene_templates';
    protected $primaryKey = 'id';

    protected $fillable = [
        'scene_id',
        'template_id',
        'is_active',
        'sort',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'scene_id' => 'integer',
        'template_id' => 'integer',
        'is_active' => 'integer',
        'sort' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

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