<?php

namespace app\model;

use app\model\BaseModel;

class MiniPageVersion extends BaseModel
{
    protected $table = 'yxshop_mini_page_versions';

    protected $softDeleteEnabled = false;

    protected $fillable = [
        'page_id', 'version_number', 'page_data',
        'summary', 'publish_at', 'publisher_id', 'app_id',
    ];

    protected $casts = [
        'version_number' => 'integer',
        'page_data' => 'array',
        'publish_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    public function page()
    {
        return $this->belongsTo(MiniPage::class, 'page_id', 'id');
    }

    public function publisher()
    {
        return $this->belongsTo(Admin::class, 'publisher_id', 'id');
    }
}