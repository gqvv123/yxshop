<?php

namespace app\model;

class AdminApiToken extends BaseModel
{
    protected $table = 'yxshop_admin_api_tokens';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    protected $softDeleteEnabled = false;

    const STATUS_DISABLED = 0;
    const STATUS_ENABLED = 1;

    protected $fillable = [
        'admin_id', 'token_name', 'token_hash', 'abilities',
        'expires_at', 'last_used_at', 'status', 'app_id'
    ];

    protected $casts = [
        'admin_id' => 'integer',
        'abilities' => 'array',
        'expires_at' => 'integer',
        'last_used_at' => 'integer',
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    public function logs()
    {
        return $this->hasMany(AdminApiTokenLog::class, 'token_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}