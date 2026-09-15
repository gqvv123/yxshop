<?php

namespace app\model;

class ArrivalNotification extends BaseModel
{
    protected $table = 'yxshop_arrival_notifications';

    protected $usesSnowflake = false;

    public $incrementing = true;

    protected $keyType = 'int';

    const NOTIFY_SITE_MESSAGE = 1;
    const NOTIFY_SMS = 2;
    const NOTIFY_EMAIL = 3;

    const STATUS_PENDING = 0;
    const STATUS_NOTIFIED = 1;

    protected $fillable = [
        'user_id', 'item_id', 'spec_id', 'notify_type', 'status',
        'notified_at', 'app_id'
    ];

    protected $casts = [
        'user_id' => 'integer',
        'item_id' => 'integer',
        'spec_id' => 'integer',
        'notify_type' => 'integer',
        'status' => 'integer',
        'notified_at' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }
}