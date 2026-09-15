<?php

namespace app\model;

class AdminLog extends BaseModel
{
    protected $table = 'yxshop_admin_logs';
    protected $softDeleteEnabled = false;
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'admin_name', 'module', 'action', 'operation_type',
        'target_type', 'target_id', 'target_name', 'request_url',
        'request_method', 'request_params', 'response_data',
        'operation_result', 'error_message', 'ip', 'user_agent', 'app_id',
        'created_at',
    ];

    protected $casts = [
        'admin_id' => 'integer',
        'operation_type' => 'integer',
        'target_id' => 'integer',
        'request_params' => 'array',
        'response_data' => 'array',
        'operation_result' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
    ];

    const OP_VIEW = 10;
    const OP_CREATE = 20;
    const OP_UPDATE = 30;
    const OP_DELETE = 40;
    const OP_EXPORT = 50;
    const OP_IMPORT = 60;
    const OP_LOGIN = 70;
    const OP_LOGOUT = 80;

    const RESULT_SUCCESS = 10;
    const RESULT_FAILED = 20;

    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }

    public function app()
    {
        return $this->belongsTo(App::class, 'app_id', 'id');
    }

    public static function record($adminId, $module, $action, $operationType = self::OP_UPDATE, $targetType = '', $targetId = 0, $targetName = '', $requestUrl = '', $requestMethod = '', $requestParams = [], $responseData = [], $operationResult = self::RESULT_SUCCESS, $errorMessage = '', $ip = '', $userAgent = '', $appId = 0)
    {
        $log = new self();
        $log->admin_id = $adminId;
        $log->admin_name = Admin::find($adminId)->username ?? '';
        $log->module = $module;
        $log->action = $action;
        $log->operation_type = $operationType;
        $log->target_type = $targetType;
        $log->target_id = $targetId;
        $log->target_name = $targetName;
        $log->request_url = $requestUrl;
        $log->request_method = $requestMethod;
        $log->request_params = $requestParams;
        $log->response_data = $responseData;
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
