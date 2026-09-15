<?php

namespace app\queue\redis;

use Webman\RedisQueue\Consumer;
use support\Db;
use support\Log;
use Webman\RedisQueue\Client;
use app\common\Logger;

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
     * @param array $data
     */
    public function consume($data)
    {
        try {
            // 记录到数据库
            $this->saveToDatabase($data);
            
            // 发送告警通知
            $this->sendAlert($data);
            
            // 记录到本地日志
            Log::info('Central log processed', $data);
        } catch (\Exception $e) {
            Logger::error('Failed to process central log: ' . $e->getMessage(), [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'log_data' => $data
            ],Logger::TYPE_SYSTEM);
        }
    }

    /**
     * 保存日志到数据库
     * @param array $data
     */
    private function saveToDatabase($data)
    {
        Db::table('yxshop_system_log')
            ->insert([
                'level' => $data['level'],
                'type' => $data['type'],
                'message' => $data['message'],
                'error_code' => $data['error_code'],
                'context' => json_encode($data['context']),
                'server_host' => $data['server']['host'],
                'server_ip' => $data['server']['ip'],
                'server_env' => $data['server']['env'],
                'trace' => json_encode($data['trace']),
                'created_at' => $data['timestamp']
            ]);
    }

    /**
     * 发送告警通知
     * @param array $data
     */
    private function sendAlert($data)
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
    private function shouldSendAlert($data)
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
    private function getAlertRules()
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
     * @param int $errorCode
     * @param int $timeWindow
     * @return int
     */
    private function getErrorCount($errorCode, $timeWindow)
    {
        $startTime = date('Y-m-d H:i:s', time() - $timeWindow);
        
        return Db::table('yxshop_system_log')
            ->where('error_code', $errorCode)
            ->where('created_at', '>=', $startTime)
            ->count();
    }
} 