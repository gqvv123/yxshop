<?php

/**
 * This file is part of webman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

return [
    'default' => [
        'handler' => support\Redis::class,
        'host' => env('redis.host', '127.0.0.1'),
        'port' => env('redis.port', 6379),
        'auth' => env('redis.password', ''),
        'database' => env('redis.database', 0),
        'timeout' => env('redis.timeout', 0),
        'retry_interval' => env('redis.retry_interval', 0),
        'read_timeout' => env('redis.read_timeout', 0),
        'prefix' => env('redis.prefix', ''),
    ],
    
    'file' => [
        'handler' => support\Cache::class,
        'path' => runtime_path() . '/cache',
    ],
    
    'memory' => [
        'handler' => support\Cache::class,
    ],
    
    // 查询缓存配置
    'query_cache' => [
        'enabled' => true,
        'ttl' => 300, // 5分钟
        'prefix' => 'query:',
    ],
    
    // 统计缓存配置
    'stats_cache' => [
        'enabled' => true,
        'ttl' => 600, // 10分钟
        'prefix' => 'stats:',
    ],
    
    // 菜单缓存配置
    'menu_cache' => [
        'enabled' => true,
        'ttl' => 1800, // 30分钟
        'prefix' => 'menu:',
    ],
];