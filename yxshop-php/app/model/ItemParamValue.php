<?php

namespace app\model;

class ItemParamValue extends BaseModel
{
    protected $table = 'yxshop_item_param_values';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'item_id', 'template_item_id', 'param_value', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'template_item_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function templateItem()
    {
        return $this->belongsTo(ParamTemplateItem::class, 'template_item_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}