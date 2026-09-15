<?php

namespace app\model;

class ItemTag extends BaseModel
{
    protected $table = 'yxshop_item_tags';

    protected $fillable = [
        'tag_name', 'tag_color', 'sort', 'status', 'app_id'
    ];

    protected $casts = [
        'sort' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public function items()
    {
        return $this->belongsToMany(Item::class, 'yxshop_item_tag_relations', 'tag_id', 'item_id');
    }
}
