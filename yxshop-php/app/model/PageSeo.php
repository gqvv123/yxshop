<?php

namespace app\model;

class PageSeo extends BaseModel
{
    protected $table = 'yxshop_page_seos';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'page_key', 'lang_code', 'title', 'description', 'keywords',
        'og_tags', 'structured_data', 'app_id'
    ];

    protected $casts = [
        'og_tags' => 'array',
        'structured_data' => 'array',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}