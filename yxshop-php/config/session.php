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

use Webman\Session\FileSessionHandler;
use Webman\Session\RedisSessionHandler;
use Webman\Session\RedisClusterSessionHandler;

return [

    // 生产环境使用 redis，开发环境可降级为 file
    'type' => env('APP_DEBUG', false) ? 'file' : 'redis',

    'handler' => env('APP_DEBUG', false) ? FileSessionHandler::class : RedisSessionHandler::class,

    'config' => [
        'file' => [
            'save_path' => runtime_path() . '/sessions',
        ],
        'redis' => [
            'host' => env('REDIS_HOST', '127.0.0.1'),
            'port' => env('REDIS_PORT', 6379),
            'auth' => env('REDIS_PASSWORD', ''),
            'timeout' => 2,
            'database' => env('REDIS_DB', 0),
            'prefix' => env('SESSION_COOKIE', 'yxshop') . '_session_',
        ],
        'redis_cluster' => [
            'host' => ['127.0.0.1:7000', '127.0.0.1:7001', '127.0.0.1:7001'],
            'timeout' => 2,
            'auth' => '',
            'prefix' => 'redis_session_',
        ]
    ],

    'session_name' => env('SESSION_COOKIE', 'PHPSID'),

    'auto_updated_atstamp' => false,

    'lifetime' => 7*24*60*60,

    'cookie_lifetime' => 365*24*60*60,

    'cookie_path' => '/',

    'domain' => env('SESSION_DOMAIN', ''),
    
    'http_only' => true,

    'secure' => env('APP_SECURE', true),
    
    'same_site' => env('APP_SAME_SITE', 'lax'),

    'gc_probability' => [1, 1000],

];
