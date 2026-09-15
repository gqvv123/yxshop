<?php

namespace app\model;

class UserLog extends BaseModel
{
    protected $table = 'yxshop_user_logs';

    protected $softDeleteEnabled = false;
    public $timestamps = false;

    protected $fillable = [
        'user_id', 'user_name', 'module', 'action', 'operation_type',
        'target_type', 'target_id', 'operation_desc', 'request_url',
        'request_method', 'operation_result', 'error_message', 'ip',
        'user_agent', 'app_id', 'created_at',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'operation_type' => 'integer',
        'target_id' => 'integer',
        'operation_result' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
    ];

    const OP_VIEW = 10;
    const OP_CREATE = 20;
    const OP_UPDATE = 30;
    const OP_DELETE = 40;
    const OP_PAY = 50;
    const OP_LOGIN = 60;
    const OP_REGISTER = 70;

    const RESULT_SUCCESS = 10;
    const RESULT_FAILED = 20;

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public static function record($userId, $module, $action, $operationType = self::OP_VIEW, $targetType = '', $targetId = 0, $operationDesc = '', $requestUrl = '', $requestMethod = '', $operationResult = self::RESULT_SUCCESS, $errorMessage = '', $ip = '', $userAgent = '', $appId = 0)
    {
        $log = new self();
        $log->user_id = $userId;
        $log->user_name = User::find($userId)->nickname ?? '';
        $log->module = $module;
        $log->action = $action;
        $log->operation_type = $operationType;
        $log->target_type = $targetType;
        $log->target_id = $targetId;
        $log->operation_desc = $operationDesc;
        $log->request_url = $requestUrl;
        $log->request_method = $requestMethod;
        $log->operation_result = $operationResult;
        $log->error_message = $errorMessage;
        $log->ip = $ip;
        $log->user_agent = $userAgent;
        $log->app_id = $appId;
        $log->created_at = time();
        $log->save();

        return $log;
    }
}
