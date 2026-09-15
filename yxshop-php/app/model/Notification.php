<?php

namespace app\model;

class Notification extends BaseModel
{
    protected $table = 'yxshop_notifications';

    protected $fillable = [
        'title','content','channel','status','app_id','created_at','updated_at'
    ];

    protected $casts = [
        'status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer'
    ];

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function reads()
    {
        return $this->hasMany(NotificationRead::class, 'notification_id');
    }

    public function template()
    {
        return $this->belongsTo(NotificationTemplate::class, 'template_id');
    }

    public function queue()
    {
        return $this->hasMany(NotificationQueue::class, 'notification_id');
    }

    public function statistics()
    {
        return $this->hasOne(NotificationStatistics::class, 'notification_id');
    }
}


