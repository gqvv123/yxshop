<?php

namespace app\queue\redis;

use Webman\RedisQueue\Consumer;
use support\Db;
use support\Log;
use support\Redis;
use app\common\LogCleanupConfig;
use app\common\Logger;
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

        } catch (\Exception $e) {
            $stats['error'] = true;
            Logger::error('日志清理失败', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], Logger::TYPE_SYSTEM);
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
    private function sendMonitorData(array $stats)
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
     * @return float
     */
    private function getTableSize(): float
    {
        $result = Db::table('yxshop_system_log')
            ->select("SELECT ROUND((data_length + index_length) / 1024 / 1024, 2) as size 
                     FROM information_schema.TABLES 
                     WHERE table_schema = DATABASE() 
                     AND table_name = 'yxshop_system_log'");
        
        return $result[0]->size ?? 0;
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
     * @return int 删除的记录数
     */
    private function cleanupByLevel(): int
    {
        $deletedCount = 0;
        foreach (LogCleanupConfig::LEVEL_RETENTION as $level => $days) {
            $deletedCount += $this->deleteOldLogs([
                'level' => $level,
                'days' => $days
            ]);
        }
        return $deletedCount;
    }

    /**
     * 删除旧日志
     * @param array $params
     * @return int 删除的记录数
     */
    private function deleteOldLogs(array $params): int
    {
        $deletedCount = 0;
        $archivedCount = 0;

        $query = Db::table('yxshop_system_log')
            ->where('created_at', '<', date('Y-m-d H:i:s', strtotime("-{$params['days']} days")));

        if (isset($params['type'])) {
            $query->where('type', $params['type']);
        }
        if (isset($params['level'])) {
            $query->where('level', $params['level']);
        }

        // 分批删除
        $query->orderBy('id')->chunk(LogCleanupConfig::BATCH_SIZE, function ($logs) use (&$deletedCount, &$archivedCount) {
            foreach ($logs as $log) {
                // 如果是错误日志，先归档
                if ($log->level === Logger::LEVEL_ERROR) {
                    $this->archiveLog($log);
                    $archivedCount++;
                }
                // 删除日志
                Db::connection('mysql_backend')
                    ->table('system_logs')
                    ->where('id', $log->id)
                    ->delete();
                $deletedCount++;
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
        $oldLogs = Db::table('yxshop_system_log')
            ->where('created_at', '<', date('Y-m-d H:i:s', strtotime("-{$threshold} days")))
            ->where('is_compressed', 0)
            ->get();

        foreach ($oldLogs as $log) {
            // 压缩日志内容
            $compressed = gzcompress(json_encode($log));
            
            // 更新日志记录
            Db::table('yxshop_system_log')
                ->where('id', $log->id)
                ->update([
                    'message' => $compressed,
                    'is_compressed' => 1,
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
            $compressedCount++;
        }

        return $compressedCount;
    }

    /**
     * 归档重要日志
     * @param object $log
     */
    private function archiveLog($log)
    {
        Db::table('yxshop_system_log_archive')
            ->insert([
                'level' => $log->level,
                'type' => $log->type,
                'message' => $log->message,
                'error_code' => $log->error_code,
                'context' => $log->context,
                'server_host' => $log->server_host,
                'server_ip' => $log->server_ip,
                'server_env' => $log->server_env,
                'trace' => $log->trace,
                'created_at' => $log->created_at,
                'archived_at' => date('Y-m-d H:i:s')
            ]);
    }

    /**
     * 消费失败回调
     */
    public function onConsumeFailure(\Throwable $e, $package)
    {
        Logger::error('日志清理任务失败', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'package' => $package
        ], Logger::TYPE_SYSTEM);
    }
} 