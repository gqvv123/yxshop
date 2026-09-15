<?php

return [
    // 应用配置
    'app' => [
        'debug' => true,
        'log' => [
            'level' => 'debug',
            'path' => runtime_path() . '/logs',
            'max_files' => 7,
        ],
    ],
    
    // 数据库配置
    'database' => [
        'default' => 'mysql',
        'connections' => [
            'mysql' => [
                'driver' => 'mysql',
                'host' => env('DB_HOST', '127.0.0.1'),
                'port' => env('DB_PORT', '3306'),
                'database' => env('DB_DATABASE', 'yxadmin_dev'),
                'username' => env('DB_USERNAME', 'root'),
                'password' => env('DB_PASSWORD', ''),
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => false,
                'engine' => 'InnoDB',
                'options' => [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ],
                'pool' => [
                    'min_connections' => 1,
                    'max_connections' => 20,
                    'connect_timeout' => 10.0,
                    'wait_timeout' => 3.0,
                    'heartbeat' => -1,
                    'max_idle_time' => (float) env('DB_MAX_IDLE_TIME', 60),
                ],
            ],
        ],
    ],
    
    // Redis配置
    'redis' => [
        'default' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_DB', 0),
            'timeout' => 0.0,
            'retry_interval' => 0,
            'read_timeout' => 0.0,
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 20,
                'connect_timeout' => 10.0,
                'wait_timeout' => 3.0,
                'heartbeat' => -1,
                'max_idle_time' => (float) env('REDIS_MAX_IDLE_TIME', 60),
            ],
        ],
        'cache' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'password' => env('REDIS_PASSWORD', null),
            'port' => env('REDIS_PORT', 6379),
            'database' => env('REDIS_CACHE_DB', 1),
            'timeout' => 0.0,
            'retry_interval' => 0,
            'read_timeout' => 0.0,
            'pool' => [
                'min_connections' => 1,
                'max_connections' => 10,
                'connect_timeout' => 10.0,
                'wait_timeout' => 3.0,
                'heartbeat' => -1,
                'max_idle_time' => (float) env('REDIS_MAX_IDLE_TIME', 60),
            ],
        ],
    ],
    
    // 缓存配置
    'cache' => [
        'default' => 'file',
        'stores' => [
            'redis' => [
                'driver' => 'redis',
                'connection' => 'cache',
                'lock_connection' => 'default',
            ],
            'file' => [
                'driver' => 'file',
                'path' => runtime_path() . '/cache',
            ],
        ],
        'prefix' => env('CACHE_PREFIX', 'yxadmin_dev_cache'),
    ],
    
    // 会话配置
    'session' => [
        'driver' => 'file',
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => runtime_path() . '/sessions',
        'connection' => env('SESSION_CONNECTION', null),
        'table' => 'sessions',
        'store' => env('SESSION_STORE', null),
        'lottery' => [2, 100],
        'cookie' => env('SESSION_COOKIE', 'yxadmin_dev_session'),
        'path' => '/',
        'domain' => env('SESSION_DOMAIN', null),
        'secure' => false,
        'http_only' => true,
        'same_site' => 'lax',
    ],
    
    // 队列配置
    'queue' => [
        'default' => env('QUEUE_CONNECTION', 'sync'),
        'connections' => [
            'sync' => [
                'driver' => 'sync',
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
                'queue' => env('REDIS_QUEUE', 'default'),
                'retry_after' => 90,
                'block_for' => null,
            ],
            'database' => [
                'driver' => 'database',
                'table' => 'jobs',
                'queue' => 'default',
                'retry_after' => 90,
                'after_commit' => false,
            ],
        ],
        'failed' => [
            'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
            'database' => env('DB_CONNECTION', 'mysql'),
            'table' => 'failed_jobs',
        ],
    ],
    
    // 邮件配置
    'mail' => [
        'default' => env('MAIL_MAILER', 'log'),
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => env('MAIL_HOST', 'smtp.mailgun.org'),
                'port' => env('MAIL_PORT', 587),
                'encryption' => env('MAIL_ENCRYPTION', 'tls'),
                'username' => env('MAIL_USERNAME'),
                'password' => env('MAIL_PASSWORD'),
                'timeout' => null,
                'local_domain' => env('MAIL_EHLO_DOMAIN'),
            ],
            'log' => [
                'transport' => 'log',
                'channel' => env('MAIL_LOG_CHANNEL'),
            ],
        ],
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'name' => env('MAIL_FROM_NAME', 'Example'),
        ],
    ],
    
    // 文件上传配置
    'upload' => [
        'disk' => env('UPLOAD_DISK', 'local'),
        'max_size' => env('UPLOAD_MAX_SIZE', 52428800), // 50MB
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx'],
        'path' => 'uploads/dev',
    ],
    
    // 日志配置
    'logging' => [
        'default' => env('LOG_CHANNEL', 'stack'),
        'deprecations' => [
            'channel' => 'null',
            'trace' => false,
        ],
        'channels' => [
            'stack' => [
                'driver' => 'stack',
                'channels' => ['single', 'daily'],
                'ignore_exceptions' => false,
            ],
            'single' => [
                'driver' => 'single',
                'path' => runtime_path() . '/logs/webman.log',
                'level' => env('LOG_LEVEL', 'debug'),
            ],
            'daily' => [
                'driver' => 'daily',
                'path' => runtime_path() . '/logs/webman.log',
                'level' => env('LOG_LEVEL', 'debug'),
                'days' => 7,
            ],
        ],
    ],
    
    // 安全配置
    'security' => [
        'csrf' => [
            'enabled' => false,
            'token_name' => '_token',
        ],
        'xss' => [
            'enabled' => false,
        ],
        'sql_injection' => [
            'enabled' => false,
        ],
    ],
    
    // 性能配置
    'performance' => [
        'cache' => [
            'enabled' => false,
            'ttl' => 300,
        ],
        'compression' => [
            'enabled' => false,
            'level' => 6,
        ],
        'optimization' => [
            'enabled' => false,
            'minify_html' => false,
            'minify_css' => false,
            'minify_js' => false,
        ],
    ],
    
    // 监控配置
    'monitoring' => [
        'enabled' => false,
        'metrics' => [
            'enabled' => false,
            'collector' => 'prometheus',
        ],
        'health_check' => [
            'enabled' => true,
            'endpoint' => '/health',
        ],
        'profiling' => [
            'enabled' => true,
            'storage' => runtime_path() . '/profiling',
        ],
    ],
]; 