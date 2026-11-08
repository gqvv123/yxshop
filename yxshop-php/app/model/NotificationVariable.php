<?php

namespace app\model;

/**
 * 通知变量模型
 */
class NotificationVariable extends BaseModel
{
    protected $table = 'yxshop_notification_variables';
    protected $primaryKey = 'id';

    protected $fillable = [
        'variable_name',
        'variable_code',
        'variable_desc',
        'variable_type',
        'variable_default',
        'is_required',
        'is_active',
        'sort',
        'app_id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_required' => 'integer',
        'is_active' => 'integer',
        'sort' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    // 变量类型常量
    const TYPE_STRING = 'string';       // 字符串
    const TYPE_INTEGER = 'integer';     // 整数
    const TYPE_FLOAT = 'float';         // 浮点数
    const TYPE_BOOLEAN = 'boolean';     // 布尔值
    const TYPE_ARRAY = 'array';         // 数组
    const TYPE_OBJECT = 'object';       // 对象

    /**
     * 获取变量类型文本
     */
    public function getVariableTypeTextAttribute()
    {
        $types = [
            self::TYPE_STRING => '字符串',
            self::TYPE_INTEGER => '整数',
            self::TYPE_FLOAT => '浮点数',
            self::TYPE_BOOLEAN => '布尔值',
            self::TYPE_ARRAY => '数组',
            self::TYPE_OBJECT => '对象',
        ];

        return $types[$this->variable_type] ?? $this->variable_type;
    }

    /**
     * 关联应用
     */
    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    /**
     * 关联模板变量
     */
    public function templateVariables()
    {
        return $this->hasMany(NotificationTemplateVariable::class, 'variable_id', 'id');
    }
}
