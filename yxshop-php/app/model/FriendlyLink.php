<?php

namespace app\model;

class FriendlyLink extends BaseModel
{
    protected $table = 'yxshop_friendly_links';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $fillable = [
        'link_name', 'link_url', 'link_logo', 'sort', 'status', 'app_id'
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
}