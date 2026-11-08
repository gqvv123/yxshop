<?php
return [
    'default' => [
        'host' => 'redis://'.env('redis.host', '127.0.0.1').':'.env('redis.port', 6379),
        'options' => [
            'auth' => null,
            'db' => 0,
            'prefix' => '',
            'max_attempts'  => 5,
            'retry_seconds' => 5,
        ],
        // Connection pool, supports only Swoole or Swow drivers.
        'pool' => [
            'max_connections' => 5,
            'min_connections' => 1,
            'wait_timeout' => 3,
            'idle_timeout' => 60,
            'heartbeat_interval' => 50,
        ]
    ],
];
