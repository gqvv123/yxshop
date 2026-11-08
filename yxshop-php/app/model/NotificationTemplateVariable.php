<?php

namespace app\model;

/**
 * 通知模板变量关联模型
 */
class NotificationTemplateVariable extends BaseModel
{
    protected $table = 'yxshop_notification_template_variables';
    protected $primaryKey = 'id';

    protected $fillable = [
        'template_id',
        'variable_id',
        'is_required',
        'sort',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'template_id' => 'integer',
        'variable_id' => 'integer',
        'is_required' => 'integer',
        'sort' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    /**
     * 关联模板
     */
    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id', 'id');
    }

    /**
     * 关联变量
     */
    public function variable()
    {
        return $this->belongsTo(NotificationVariable::class, 'variable_id', 'id');
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
