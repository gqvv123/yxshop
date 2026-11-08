<?php

namespace app\common;

use support\Log;
use Webman\RedisQueue\Client;

class Logger
{
    // 日志级别
    const LEVEL_ERROR = 'error';
    const LEVEL_WARNING = 'warning';
    const LEVEL_INFO = 'info';
    const LEVEL_DEBUG = 'debug';

    // 日志类型
    const TYPE_SYSTEM = 'system';
    const TYPE_BUSINESS = 'business';
    const TYPE_TRADE = 'trade';
    const TYPE_USER = 'user';
    const TYPE_NOTIFICATION = 'notification';

    /**
     * 记录错误日志
     * @param string $message 错误信息
     * @param array $context 上下文信息
     * @param string $type 日志类型
     * @param int $errorCode 错误码
     * @param bool $central 是否投递到集中式日志队列（消费失败重入场景必须传 false）
     */
    public static function error(
        string $message,
        array $context = [],
        string $type = self::TYPE_SYSTEM,
        int $errorCode = 0,
        bool $central = true
    ): void {
        self::log(self::LEVEL_ERROR, $message, $context, $type, $errorCode, $central);
    }

    /**
     * 记录警告日志
     * @param string $message 警告信息
     * @param array $context 上下文信息
     * @param string $type 日志类型
     */
    public static function warning(string $message, array $context = [], string $type = self::TYPE_SYSTEM): void
    {
        self::log(self::LEVEL_WARNING, $message, $context, $type);
    }

    /**
     * 记录信息日志
     * @param string $message 信息
     * @param array $context 上下文信息
     * @param string $type 日志类型
     */
    public static function info(string $message, array $context = [], string $type = self::TYPE_SYSTEM): void
    {
        self::log(self::LEVEL_INFO, $message, $context, $type);
    }

    /**
     * 记录调试日志
     * @param string $message 调试信息
     * @param array $context 上下文信息
     * @param string $type 日志类型
     */
    public static function debug(string $message, array $context = [], string $type = self::TYPE_SYSTEM): void
    {
        self::log(self::LEVEL_DEBUG, $message, $context, $type);
    }

    /**
     * 统一日志记录方法
     * @param string $level 日志级别
     * @param string $message 日志信息
     * @param array $context 上下文信息
     * @param string $type 日志类型
     * @param int $errorCode 错误码
     * @param bool $central 是否投递到集中式日志队列
     */
    private static function log(
        string $level,
        string $message,
        array $context = [],
        string $type = self::TYPE_SYSTEM,
        int $errorCode = 0,
        bool $central = true
    ): void {
        // 构建日志数据
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => $level,
            'type' => $type,
            'message' => $message,
            'error_code' => $errorCode,
            'context' => $context,
            'server' => [
                'host' => gethostname(),
                'ip' => $_SERVER['SERVER_ADDR'] ?? '',
                'env' => env('APP_ENV', 'production')
            ],
            'trace' => debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1] ?? []
        ];

        // 记录到本地日志
        Log::{$level}($message, $logData);

        // 如果是错误日志，发送到集中式日志系统
        // 注意：CentralLog 消费失败时必须以 $central=false 调用本方法，否则
        // "消费失败 -> 再入队 -> 再失败" 会形成自激循环（历史上把日志撑到 300MB+）
        if ($level === self::LEVEL_ERROR && $central) {
            self::sendToCentralLog($logData);
        }
    }

    /**
     * 发送日志到集中式日志系统
     * @param array $logData 日志数据
     */
    private static function sendToCentralLog(array $logData): void
    {
        try {
            // 发送到 Redis 队列
            Client::send('Central-Log', $logData);
        } catch (\Exception $e) {
            // 如果发送失败，记录到本地
            Log::error('Failed to send log to central system: ' . $e->getMessage());
        }
    }
}