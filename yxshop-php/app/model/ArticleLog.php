<?php

namespace app\model;

class ArticleLog extends BaseModel
{
    protected $table = 'yxshop_article_logs';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'article_id', 'action', 'before_status', 'after_status',
        'actor_id', 'actor_name', 'remark', 'extra',
    ];

    protected $casts = [
        'article_id' => 'integer',
        'before_status' => 'integer',
        'after_status' => 'integer',
        'actor_id' => 'integer',
        'created_at' => 'integer',
    ];

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id', 'id');
    }

    public function actor()
    {
        return $this->belongsTo(Admin::class, 'actor_id', 'id');
    }
}
