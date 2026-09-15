<?php

namespace app\model;

class ArticleCategory extends BaseModel
{
    protected $table = 'yxshop_article_categories';

    protected $fillable = [
        'name', 'parent_id', 'sort', 'status', 'app_id',
        'created_at', 'updated_at'
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function parent()
    {
        return $this->belongsTo(ArticleCategory::class, 'parent_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(ArticleCategory::class, 'parent_id', 'id');
    }
}


