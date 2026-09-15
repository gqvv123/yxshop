<?php

namespace app\model;

class ItemAttr extends BaseModel
{
    protected $table = 'yxshop_item_attrs';

    protected $fillable = [
        'item_id', 'attr_id', 'attr_value', 'attr_price', 'app_id'
    ];

    protected $casts = [
        'item_id' => 'integer',
        'attr_id' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function attribute()
    {
        return $this->belongsTo(ItemAttribute::class, 'attr_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}
