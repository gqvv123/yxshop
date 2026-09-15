<?php

namespace app\common;

class LogCleanupMonitor
{
    // 监控指标
    const METRIC_CLEANUP_COUNT = 'cleanup_count';           // 清理次数
    const METRIC_CLEANUP_DURATION = 'cleanup_duration';     // 清理耗时
    const METRIC_DELETED_COUNT = 'deleted_count';          // 删除记录数
    const METRIC_ARCHIVED_COUNT = 'archived_count';        // 归档记录数
    const METRIC_COMPRESSED_COUNT = 'compressed_count';    // 压缩记录数
    const METRIC_ERROR_COUNT = 'error_count';              // 错误次数

    // 监控阈值
    const THRESHOLD_CLEANUP_DURATION = 3600;    // 清理任务最大执行时间（秒）
    const THRESHOLD_ERROR_COUNT = 3;            // 连续错误次数阈值
    const THRESHOLD_TABLE_SIZE = 10240;         // 表大小阈值（MB）

    // 监控时间窗口
    const WINDOW_1H = 3600;                     // 1小时
    const WINDOW_24H = 86400;                   // 24小时
    const WINDOW_7D = 604800;                   // 7天

    /**
     * 获取监控指标键名
     * @param string $metric
     * @param string $window
     * @return string
     */
    public static function getMetricKey(string $metric, string $window): string
    {
        return "log_cleanup:{$metric}:{$window}";
    }

    /**
     * 获取监控告警键名
     * @param string $type
     * @return string
     */
    public static function getAlertKey(string $type): string
    {
        return "log_cleanup:alert:{$type}";
    }
} 