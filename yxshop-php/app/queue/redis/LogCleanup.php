<?php

namespace app\queue\redis;

use Webman\RedisQueue\Consumer;
use support\Db;
use support\Log;
use support\Redis;
use app\common\LogCleanupConfig;
use app\common\Logger;
use app\model\SystemLog;
use Webman\RedisQueue\Client;

class LogCleanup implements Consumer
{
    /**
     * @var string 队列名称
     */
    public $queue = 'Log-Cleanup';

    /**
     * @var string Redis连接名
     */
    public $connection = 'default';

    /**
     * 处理日志清理
     * @param array $data
     */
    public function consume($data)
    {
        $startTime = microtime(true);
        $stats = [
            'deleted_count' => 0,
            'archived_count' => 0,
            'compressed_count' => 0,
            'error' => false
        ];

        try {
            // 检查是否需要清理
            if (!$this->shouldCleanup()) {
                return;
            }

            // 按类型清理日志
            $stats['deleted_count'] += $this->cleanupByType();

            // 按级别清理日志
            $stats['deleted_count'] += $this->cleanupByLevel();

            // 检查并压缩旧日志
            if (LogCleanupConfig::ENABLE_COMPRESSION) {
                $stats['compressed_count'] = $this->compressOldLogs();
            }

            // 记录清理结果
            Logger::info('日志清理完成', [
                'timestamp' => date('Y-m-d H:i:s'),
                'stats' => $stats
            ], Logger::TYPE_SYSTEM);

        } catch (\Throwable $e) {
            $stats['error'] = true;
            // central=false：清理任务自身失败不再回投日志队列，避免队列自我触发
            Logger::error('日志清理失败', [
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 4000)
            ], Logger::TYPE_SYSTEM, 0, false);
        }

        // 计算执行时间
        $duration = round(microtime(true) - $startTime, 2);
        $stats['duration'] = $duration;

        // 发送监控数据
        $this->sendMonitorData($stats);
    }

    /**
     * 发送监控数据
     * @param array $stats
     */
    private function sendMonitorData(array $stats): void
    {
        Client::send(
            'Log-Cleanup-Monitor',
            json_encode($stats)
        );
    }

    /**
     * 检查是否需要清理
     * @return bool
     */
    private function shouldCleanup(): bool
    {
        // 检查表大小
        $tableSize = $this->getTableSize();
        if ($tableSize >= LogCleanupConfig::MAX_TABLE_SIZE) {
            return true;
        }

        // 检查上次清理时间
        $lastCleanup = Redis::get('last_log_cleanup');
        if (!$lastCleanup) {
            return true;
        }

        $hoursSinceLastCleanup = (time() - $lastCleanup) / 3600;
        return $hoursSinceLastCleanup >= LogCleanupConfig::CLEANUP_INTERVAL;
    }

    /**
     * 获取表大小（MB）
     *
     * 原实现写成 Db::table()->select("SELECT ...")，select() 返回的是 Query\Builder
     * 而不是结果集，下面按数组取 $result[0] 会抛
     * "Cannot use object of type Illuminate\Database\Query\Builder as array"，
     * 而这个异常又会被写进不存在的日志表，最终演变成日志自激膨胀。
     *
     * @return float
     */
    private function getTableSize(): float
    {
        $row = Db::selectOne(
            'SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) AS size
             FROM information_schema.TABLES
             WHERE table_schema = DATABASE() AND table_name = ?',
            ['yxshop_system_logs']
        );

        return (float) ($row->size ?? 0);
    }

    /**
     * 按类型清理日志
     * @return int 删除的记录数
     */
    private function cleanupByType(): int
    {
        $deletedCount = 0;
        foreach (LogCleanupConfig::RETENTION_PERIODS as $type => $days) {
            $deletedCount += $this->deleteOldLogs([
                'type' => $type,
                'days' => $days
            ]);
        }
        return $deletedCount;
    }

    /**
     * 按级别清理日志
     *
     * 注意：表里 log_level 是数字（10/20/30/40/50，见 SystemLog 常量），
     * 配置里的 key 是字符串（error/warning/info/debug），必须映射后再查询，
     * 否则 where('log_level', 'error') 永远匹配不到任何行。
     *
     * @return int 删除的记录数
     */
    private function cleanupByLevel(): int
    {
        $levelMap = [
            Logger::LEVEL_ERROR   => SystemLog::LEVEL_ERROR,
            Logger::LEVEL_WARNING => SystemLog::LEVEL_WARNING,
            Logger::LEVEL_INFO    => SystemLog::LEVEL_INFO,
            Logger::LEVEL_DEBUG   => SystemLog::LEVEL_DEBUG,
        ];

        $deletedCount = 0;
        foreach (LogCleanupConfig::LEVEL_RETENTION as $level => $days) {
            $numeric = $levelMap[$level] ?? null;
            if ($numeric === null) {
                continue;
            }
            $deletedCount += $this->deleteOldLogs([
                'level' => $numeric,
                'days'  => $days,
            ]);
        }
        return $deletedCount;
    }

    /**
     * 删除旧日志
     *
     * 字段名对齐 yxshop_system_logs 真实 schema：log_type / log_level（不是 type / level）
     *
     * @param array $params
     * @return int 删除的记录数
     */
    private function deleteOldLogs(array $params): int
    {
        $deletedCount = 0;
        $archivedCount = 0;

        $query = Db::table('yxshop_system_logs')
            // created_at 是整型时间戳，必须与时间戳比较；
            // 之前误用日期字符串比较，条件恒不成立，导致清理任务形同虚设
            ->where('created_at', '<', time() - ((int) $params['days'] * 86400));

        if (isset($params['type'])) {
            $query->where('log_type', $params['type']);
        }
        if (isset($params['level'])) {
            $query->where('log_level', $params['level']);
        }

        // 分批删除
        $query->orderBy('id')->chunk(LogCleanupConfig::BATCH_SIZE, function ($logs) use (&$deletedCount, &$archivedCount) {
            if (!$logs->isEmpty()) {
                // 错误日志先归档（归档仍需逐条处理，量级小）
                foreach ($logs as $log) {
                    if ((int) $log->log_level === SystemLog::LEVEL_ERROR) {
                        $this->archiveLog($log);
                        $archivedCount++;
                    }
                }

                // 批量删除，避免逐条 delete 造成 N+1
                $deleteIds = $logs->map(fn($log) => (int) $log->id)->all();
                $deletedCount += (int) Db::table('yxshop_system_logs')
                    ->whereIn('id', $deleteIds)
                    ->delete();
            }
        });

        return $deletedCount;
    }

    /**
     * 压缩旧日志
     * @return int 压缩的记录数
     */
    private function compressOldLogs(): int
    {
        $compressedCount = 0;
        $threshold = LogCleanupConfig::COMPRESSION_THRESHOLD;
        $oldLogs = Db::table('yxshop_system_logs')
            ->where('created_at', '<', time() - ((int) $threshold * 86400))
            ->where('is_compressed', 0)
            ->get();

        if ($oldLogs->isNotEmpty()) {
            $compressedCount = $oldLogs->count();
            // 压缩内容是二进制，无法安全拼进 SQL 字面量，
            // 因此用 INSERT ... ON DUPLICATE KEY UPDATE 一次批量写回（值全部走参数绑定）
            $rows = [];
            $bindings = [];
            $now = time();   // updated_at 是整型时间戳
            foreach ($oldLogs as $log) {
                $rows[] = '(?, ?, 1, ?)';
                $bindings[] = (int) $log->id;
                // message 是 utf8mb4 字段，存不了 gzip 二进制，统一 base64 编码
                $bindings[] = \app\common\LogPayload::encode(json_encode($log, JSON_UNESCAPED_UNICODE));
                $bindings[] = $now;
            }

            Db::statement(
                'INSERT INTO yxshop_system_logs (id, message, is_compressed, updated_at) VALUES '
                . implode(', ', $rows)
                . ' ON DUPLICATE KEY UPDATE message = VALUES(message), '
                . 'is_compressed = VALUES(is_compressed), updated_at = VALUES(updated_at)',
                $bindings
            );
        }

        return $compressedCount;
    }

    /**
     * 归档重要日志
     * @param object $log
     */
    private function archiveLog($log): void
    {
        Db::table('yxshop_system_logs_archive')->insert([
            'id'          => (int) $log->id,
            'log_level'   => $log->log_level ?? null,
            'log_type'    => $log->log_type ?? null,
            'module'      => $log->module ?? null,
            'action'      => $log->action ?? null,
            'message'     => $log->message ?? null,
            'context'     => $log->context ?? null,
            'file'        => $log->file ?? null,
            'line'        => $log->line ?? null,
            'trace'       => $log->trace ?? null,
            'ip'          => $log->ip ?? null,
            'user_agent'  => $log->user_agent ?? null,
            'app_id'      => $log->app_id ?? 0,
            'created_at'  => $log->created_at ?? time(),
            'archived_at' => time(),
        ]);
    }

    /**
     * 消费失败回调
     */
    public function onConsumeFailure(\Throwable $e, $package): void
    {
        // central=false：同上，避免失败消息回流日志队列形成自激
        Logger::error('日志清理任务失败', [
            'error' => $e->getMessage(),
            'trace' => substr($e->getTraceAsString(), 0, 4000),
            'package' => is_scalar($package) ? $package : json_encode($package, JSON_UNESCAPED_UNICODE)
        ], Logger::TYPE_SYSTEM, 0, false);
    }
}