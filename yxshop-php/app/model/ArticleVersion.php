<?php

namespace app\model;

class ArticleVersion extends BaseModel
{
    protected $table = 'yxshop_article_versions';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    public $timestamps = false;

    protected $fillable = [
        'article_id', 'version', 'title', 'subtitle', 'content',
        'excerpt', 'cover_image', 'category_id', 'author',
        'modifier_id', 'modifier_name', 'change_summary',
        'updated_at',
    ];

    protected $casts = [
        'article_id' => 'integer',
        'version' => 'integer',
        'category_id' => 'integer',
        'modifier_id' => 'integer',
        'created_at' => 'integer',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'id');
    }

    public function modifier()
    {
        return $this->belongsTo(Admin::class, 'modifier_id', 'id');
    }
}
