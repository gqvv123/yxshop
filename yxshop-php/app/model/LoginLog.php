<?php

namespace app\model;

class LoginLog extends BaseModel
{
    protected $table = 'yxshop_login_logs';

    protected $fillable = [
        'user_type', 'user_id', 'user_name', 'login_type', 'login_result',
        'fail_reason', 'login_ip', 'user_agent', 'location', 'app_id',
        'created_at', 'login_message', 'login_status', 'updated_at',
    ];

    protected $casts = [
        'user_id' => 'string',
        'login_type' => 'integer',
        'login_result' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    const USER_TYPE_USER = 'user';
    const USER_TYPE_ADMIN = 'admin';

    const LOGIN_TYPE_PASSWORD = 10;
    const LOGIN_TYPE_WECHAT = 20;
    const LOGIN_TYPE_SMS = 30;
    const LOGIN_TYPE_SECURITY_CHANGE = 50;
    const LOGIN_TYPE_ADMIN_UNLOCK = 60;

    const RESULT_SUCCESS = 10;
    const RESULT_FAILED = 20;

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    public function getUserTypeTextAttribute()
    {
        $types = [
            self::USER_TYPE_USER => '用户',
            self::USER_TYPE_ADMIN => '管理员',
        ];

        return $types[$this->user_type] ?? '未知';
    }

    public function getLoginResultTextAttribute()
    {
        $results = [
            self::RESULT_SUCCESS => '成功',
            self::RESULT_FAILED => '失败',
        ];

        return $results[$this->login_result] ?? '未知';
    }

    public function user()
    {
        if ($this->user_type === self::USER_TYPE_ADMIN) {
            return $this->belongsTo(Admin::class, 'user_id', 'id');
        }
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public static function record($userId, $userType, $loginIp, $loginResult, $loginMessage = '', $appId = 0, $userName = '', $loginType = self::LOGIN_TYPE_PASSWORD, $failReason = '', $userAgent = '', $location = '')
    {
        $log = new self();
        $log->user_type = $userType;
        $log->user_id = $userId;
        $log->user_name = $userName;
        $log->login_type = $loginType;
        $log->login_result = $loginResult;
        $log->fail_reason = $failReason;
        $log->login_ip = $loginIp;
        $log->user_agent = $userAgent;
        $log->location = $location;
        $log->app_id = $appId;
        $log->created_at = time();
        $log->login_message = $loginMessage;
        $log->login_status = $loginResult === self::RESULT_SUCCESS ? self::STATUS_SUCCESS : self::STATUS_FAILED;
        $log->updated_at = time();
        $log->save();

        return $log;
    }
}
