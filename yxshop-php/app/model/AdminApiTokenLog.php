<?php

namespace app\model;

class AdminApiTokenLog extends BaseModel
{
    protected $table = 'yxshop_admin_api_token_logs';


    protected $softDeleteEnabled = false;

    public $timestamps = false;

    protected $fillable = [
        'token_id', 'request_path', 'request_method', 'ip_address',
        'requested_at', 'app_id', 'created_at'
    ];

    protected $casts = [
        'token_id' => 'integer',
        'requested_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer'
    ];

    public function token()
    {
        return $this->belongsTo(AdminApiToken::class, 'token_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}