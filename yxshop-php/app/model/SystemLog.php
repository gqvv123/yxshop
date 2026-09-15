<?php

namespace app\model;

class SystemLog extends BaseModel
{
    protected $table = 'yxshop_system_logs';

    protected $fillable = [
        'log_level', 'log_type', 'module', 'action', 'message', 'context',
        'file', 'line', 'trace', 'ip', 'user_agent', 'app_id', 'created_at',
        'updated_at',
    ];

    protected $casts = [
        'log_level' => 'integer',
        'line' => 'integer',
        'app_id' => 'integer',
        'created_at' => 'integer',
        'updated_at' => 'integer',
    ];

    const LEVEL_DEBUG = 10;
    const LEVEL_INFO = 20;
    const LEVEL_WARNING = 30;
    const LEVEL_ERROR = 40;
    const LEVEL_CRITICAL = 50;

    public static function record($level, $message, $context = null, $file = '', $line = 0, $trace = '', $logType = '', $module = '', $action = '', $ip = '', $userAgent = '', $appId = 0)
    {
        try {
            return static::create([
                'log_level' => $level,
                'log_type' => $logType,
                'module' => $module,
                'action' => $action,
                'message' => is_string($message) ? $message : json_encode($message, JSON_UNESCAPED_UNICODE),
                'context' => is_array($context) ? json_encode($context, JSON_UNESCAPED_UNICODE) : (string) ($context ?? ''),
                'file' => $file,
                'line' => $line,
                'trace' => is_array($trace) ? json_encode($trace, JSON_UNESCAPED_UNICODE) : (string) $trace,
                'ip' => $ip,
                'user_agent' => $userAgent,
                'app_id' => $appId,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
        } catch (\Throwable $e) {
            error_log('[SystemLog] record failed: ' . $e->getMessage());
            return null;
        }
    }
}
