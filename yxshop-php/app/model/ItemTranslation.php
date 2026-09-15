<?php

namespace app\model;

class ItemTranslation extends BaseModel
{
    protected $table = 'yxshop_item_translations';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'item_id', 'lang_code', 'field_name', 'field_value', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}