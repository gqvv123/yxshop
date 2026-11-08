<?php

namespace app\queue\redis;

use Webman\RedisQueue\Consumer;
use support\Log;
use Webman\RedisQueue\Client;
use app\common\Logger;
use app\model\SystemLog;

class CentralLog implements Consumer
{
    /**
     * @var string 队列名称
     */
    public $queue = 'Central-Log';

    /**
     * @var string Redis连接名
     */
    public $connection = 'default';

    /**
     * 处理日志消息
     *
     * 重要：catch 里必须用 $central=false 记录错误。
     * 否则 "消费失败 -> Logger::error -> 再入队 -> 再消费失败" 会形成自激循环，
     * 每轮还嵌套上一轮的完整消息体，历史上曾把 webman 日志撑到 300MB+。
     *
     * @param array $data
     */
    public function consume($data): void
    {
        try {
            // 记录到数据库
            $this->saveToDatabase($data);

            // 发送告警通知
            $this->sendAlert($data);

            // 记录到本地日志
            Log::info('Central log processed', $data);
        } catch (\Throwable $e) {
            // Throwable 而非 Exception：SQL/类型错误在 PHP8 里可能是 Error
            Logger::error('Failed to process central log: ' . $e->getMessage(), [
                // 只保留关键字段，避免把整条（可能已嵌套的）消息再复制一遍
                'error' => $e->getMessage(),
                'trace' => substr($e->getTraceAsString(), 0, 4000),
                'origin_level' => $data['level'] ?? '',
                'origin_type' => $data['type'] ?? '',
                'origin_message' => mb_substr((string) ($data['message'] ?? ''), 0, 500),
            ], Logger::TYPE_SYSTEM, 0, false);
        }
    }

    /**
     * 保存日志到数据库
     *
     * 统一走 SystemLog::record()：表 yxshop_system_logs 的真实列是
     * log_level(数字) / log_type / module / action / message / context / trace / created_at(int)，
     * 而不是 level/type/error_code/server_host 那一套。此前手写 insert 的字段全不存在，
     * 必然抛 "Unknown column" 并触发消费失败。
     *
     * @param array $data
     */
    private function saveToDatabase($data): void
    {
        $levelMap = [
            'debug'    => SystemLog::LEVEL_DEBUG,
            'info'     => SystemLog::LEVEL_INFO,
            'warning'  => SystemLog::LEVEL_WARNING,
            'error'    => SystemLog::LEVEL_ERROR,
            'critical' => SystemLog::LEVEL_CRITICAL,
        ];
        $level = $levelMap[$data['level'] ?? 'error'] ?? SystemLog::LEVEL_ERROR;

        // context/trace 可能很大（历史上有嵌套膨胀问题），入库前截断，避免撑爆 mediumtext
        $context = [
            'error_code' => $data['error_code'] ?? 0,
            'server'     => $data['server'] ?? [],
            'payload'    => $data['context'] ?? [],
        ];
        $contextJson = mb_substr(json_encode($context, JSON_UNESCAPED_UNICODE), 0, 60000);
        $traceJson = mb_substr(
            is_array($data['trace'] ?? null)
                ? json_encode($data['trace'], JSON_UNESCAPED_UNICODE)
                : (string) ($data['trace'] ?? ''),
            0,
            60000
        );

        SystemLog::record(
            $level,
            mb_substr((string) ($data['message'] ?? ''), 0, 2000),
            $contextJson,
            '',
            0,
            $traceJson,
            (string) ($data['type'] ?? 'system'),
            'central_log'
        );
    }

    /**
     * 发送告警通知
     * @param array $data
     */
    private function sendAlert($data): void
    {
        // 如果是错误日志，发送告警
        if ($data['level'] === 'error') {
            // 根据错误类型和级别决定是否发送告警
            $shouldAlert = $this->shouldSendAlert($data);

            if ($shouldAlert) {
                // 发送到告警队列
                Client::send('Alert-Notification', [
                    'type' => 'error_alert',
                    'data' => $data
                ]);
            }
        }
    }

    /**
     * 判断是否需要发送告警
     * @param array $data
     * @return bool
     */
    private function shouldSendAlert($data): bool
    {
        // 获取告警规则
        $rules = $this->getAlertRules();

        // 检查是否匹配任何规则
        foreach ($rules as $rule) {
            if ($this->matchRule($data, $rule)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取告警规则
     * @return array
     */
    private function getAlertRules(): array
    {
        // 从数据库或配置文件获取告警规则
        return [
            [
                'type' => 'system',
                'level' => 'error',
                'error_codes' => [1000, 1001, 1002], // 系统级错误
                'threshold' => 5, // 5分钟内出现5次
                'time_window' => 300 // 5分钟
            ],
            [
                'type' => 'business',
                'level' => 'error',
                'error_codes' => [2000, 2001, 2002], // 业务级错误
                'threshold' => 10,
                'time_window' => 300
            ]
        ];
    }

    /**
     * 检查日志是否匹配规则
     * @param array $data
     * @param array $rule
     * @return bool
     */
    private function matchRule($data, $rule)
    {
        // 检查类型和级别
        if ($data['type'] !== $rule['type'] || $data['level'] !== $rule['level']) {
            return false;
        }

        // 检查错误码
        if (!in_array($data['error_code'], $rule['error_codes'])) {
            return false;
        }

        // 检查频率
        $count = $this->getErrorCount($data['error_code'], $rule['time_window']);
        return $count >= $rule['threshold'];
    }

    /**
     * 获取指定时间窗口内的错误次数
     *
     * created_at 是 int 时间戳，必须用时间戳比较；
     * 用 date('Y-m-d H:i:s') 字符串会因隐式转数字导致条件恒不成立。
     *
     * @param int $errorCode
     * @param int $timeWindow
     * @return int
     */
    private function getErrorCount($errorCode, $timeWindow)
    {
        return SystemLog::where('log_level', SystemLog::LEVEL_ERROR)
            ->where('created_at', '>=', time() - (int) $timeWindow)
            ->count();
    }
}