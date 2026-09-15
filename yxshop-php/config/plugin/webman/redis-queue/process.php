<?php
return [
    'consumer'  => [
        'handler'     => Webman\RedisQueue\Process\Consumer::class,
        'count'       => 8, // 可以设置多进程同时消费
        'constructor' => [
            // 消费者类目录
            'consumer_dir' => app_path() . '/queue/redis'
        ]
    ],
    'log_cleanup' => [
        'handler'     => app\queue\redis\LogCleanup::class,
        'count'       => 1, // 可以设置多进程同时消费
        'constructor' => [
            'producer_dir' => app_path() . '/queue/redis'
        ]
    ],
    'log_cleanup_monitor' => [
        'handler'     => app\queue\redis\LogCleanupMonitor::class,
        'count'       => 1, // 可以设置多进程同时消费
        'constructor' => [
            'producer_dir' => app_path() . '/queue/redis'
        ]
    ],
    'central_log' => [
        'handler'     => app\queue\redis\CentralLog::class,
        'count'       => 1, // 可以设置多进程同时消费
        'constructor' => [
            'producer_dir' => app_path() . '/queue/redis'
        ]
    ]
];