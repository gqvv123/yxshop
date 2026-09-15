<?php

namespace app\common;

class LogCleanupConfig
{
    // 日志保留时间（天）
    const RETENTION_PERIODS = [
        Logger::TYPE_SYSTEM => 30,        // 系统日志保留30天
        Logger::TYPE_BUSINESS => 90,      // 业务日志保留90天
        Logger::TYPE_TRADE => 180,        // 事务日志保留180天
        Logger::TYPE_USER => 90,          // 用户日志保留90天
        Logger::TYPE_NOTIFICATION => 30,  // 通知日志保留30天
    ];

    // 日志级别保留策略
    const LEVEL_RETENTION = [
        Logger::LEVEL_ERROR => 365,       // 错误日志保留1年
        Logger::LEVEL_WARNING => 180,     // 警告日志保留180天
        Logger::LEVEL_INFO => 90,         // 信息日志保留90天
        Logger::LEVEL_DEBUG => 30,        // 调试日志保留30天
    ];

    // 数据库表大小限制（MB）
    const MAX_TABLE_SIZE = 10240;         // 10GB

    // 单次清理的批量大小
    const BATCH_SIZE = 1000;

    // 清理间隔（小时）
    const CLEANUP_INTERVAL = 24;

    // 是否启用压缩
    const ENABLE_COMPRESSION = true;

    // 压缩阈值（天）
    const COMPRESSION_THRESHOLD = 30;

    /**
     * 获取日志类型的保留时间
     * @param string $type
     * @return int
     */
    public static function getRetentionPeriod(string $type): int
    {
        return self::RETENTION_PERIODS[$type] ?? 30;
    }

    /**
     * 获取日志级别的保留时间
     * @param string $level
     * @return int
     */
    public static function getLevelRetention(string $level): int
    {
        return self::LEVEL_RETENTION[$level] ?? 30;
    }
} 