<?php

namespace app\model;

class ParamTemplate extends BaseModel
{
    protected $table = 'yxshop_param_templates';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $fillable = [
        'template_name', 'category_id', 'sort', 'status', 'app_id'
    ];

    protected $casts = [
        'category_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id', 'id');
    }

    public function items()
    {
        return $this->hasMany(ParamTemplateItem::class, 'template_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}