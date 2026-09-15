<?php
namespace app\queue\redis;

use Webman\RedisQueue\Consumer;
use support\Log;

class LogCleanupMonitor implements Consumer
{
    public $queue = 'Log-Cleanup-Monitor';
    public $connection = 'default';

    public function consume($data)
    {
        // 处理监控数据
        Log::info('Log cleanup monitor data: ' . $data);
    }
}

