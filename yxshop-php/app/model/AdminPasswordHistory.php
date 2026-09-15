<?php

namespace app\model;

class AdminPasswordHistory extends BaseModel
{
    protected $table = 'yxshop_admin_password_histories';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const UPDATED_AT = null;

    protected $fillable = ['admin_id', 'password_hash', 'created_at'];

    protected $casts = [
        'admin_id' => 'integer',
        'created_at' => 'integer',
    ];

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }
}