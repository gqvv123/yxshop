<?php

namespace app\model;

class Brand extends BaseModel
{
    protected $table = 'yxshop_brands';

    protected $fillable = [
        'name', 'logo', 'phone', 'address', 'desc', 'url',
        'seo_title', 'seo_keywords', 'seo_description', 'sort',
        'cat_name', 'parent_cat_id', 'cat_id', 'is_hot', 'app_id', 'status'
    ];

    protected $casts = [
        'sort' => 'integer',
        'parent_cat_id' => 'integer',
        'cat_id' => 'integer',
        'is_hot' => 'integer',
        'app_id' => 'integer',
        'status' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function items()
    {
        return $this->hasMany(Item::class, 'brand_id', 'id');
    }

    public function getItemCountAttribute()
    {
        if ($this->relationLoaded('items')) {
            return $this->getRelation('items')->count();
        }
        return $this->items()->count();
    }

    public function hasItems()
    {
        if ($this->relationLoaded('items')) {
            return $this->getRelation('items')->count() > 0;
        }
        return $this->items()->count() > 0;
    }
}
