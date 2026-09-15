<?php

namespace app\model;

class ParamTemplateItem extends BaseModel
{
    protected $table = 'yxshop_param_template_items';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    const PARAM_TYPE_TEXT = 'text';
    const PARAM_TYPE_NUMBER = 'number';
    const PARAM_TYPE_ENUM = 'enum';

    protected $fillable = [
        'template_id', 'param_name', 'param_type', 'param_values',
        'sort', 'is_required', 'app_id'
    ];

    protected $casts = [
        'template_id' => 'integer',
        'param_values' => 'array',
        'sort' => 'integer',
        'is_required' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function template()
    {
        return $this->belongsTo(ParamTemplate::class, 'template_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}