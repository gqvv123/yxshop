<?php

namespace app\model;

class SmsLog extends BaseModel
{
    protected $table = 'yxshop_sms_logs';
    protected $softDeleteEnabled = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'phone', 'sms_type', 'sms_content', 'sms_status',
        'sms_result', 'error_message', 'ip', 'app_id', 'created_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'sms_type' => 'integer',
        'sms_status' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
    ];

    const TYPE_REGISTER = 10;
    const TYPE_LOGIN = 20;
    const TYPE_RESET_PASSWORD = 30;
    const TYPE_PAYMENT = 40;
    const TYPE_NOTIFICATION = 50;

    const STATUS_PENDING = 10;
    const STATUS_SENDING = 20;
    const STATUS_SUCCESS = 30;
    const STATUS_FAILED = 40;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public static function record($phone, $smsContent, $smsType = self::TYPE_REGISTER, $userId = 0, $appId = 0)
    {
        $log = new self();
        $log->user_id = $userId;
        $log->phone = $phone;
        $log->sms_type = $smsType;
        $log->sms_content = $smsContent;
        $log->sms_status = self::STATUS_PENDING;
        $log->sms_result = '';
        $log->error_message = '';
        $log->ip = '';
        $log->app_id = $appId;
        $log->created_at = time();
        $log->save();

        return $log;
    }
}
