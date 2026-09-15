<?php

// 微信小程序节点：同时按 mini_program / miniprogram 两种 key 暴露，
// 业务代码任意写法都能取到，避免历史误用
$miniProgram = [
    'app_id' => env('WECHAT_MINI_APPID', env('wechat.mini_program.app_id')),
    'secret' => env('WECHAT_MINI_SECRET', env('wechat.mini_program.secret')),
    'token' => env('WECHAT_MINI_TOKEN', env('wechat.mini_program.token')),
    'aes_key' => env('WECHAT_MINI_AES_KEY', env('wechat.mini_program.aes_key')),
];

return [
    // 小程序配置（推荐 key）
    'miniprogram' => $miniProgram,
    // 兼容旧引用
    'mini_program' => $miniProgram,
    
    // 支付配置
    'pay' => [
        'app_id' => env('wechat.pay.app_id'),
        'mch_id' => env('wechat.pay.mch_id'),
        'key' => env('wechat.pay.key'),
        'cert_path' => env('wechat.pay.cert_path'),
        'key_path' => env('wechat.pay.key_path'),
        'notify_url' => env('wechat.pay.notify_url'),
    ],
    
    // 公众号配置
    'official_account' => [
        'app_id' => env('wechat.official_account.app_id'),
        'secret' => env('wechat.official_account.secret'),
        'token' => env('wechat.official_account.token'),
        'aes_key' => env('wechat.official_account.aes_key'),
    ],
    
    // 开放平台配置
    'open_platform' => [
        'app_id' => env('wechat.open_platform.app_id'),
        'secret' => env('wechat.open_platform.secret'),
        'token' => env('wechat.open_platform.token'),
        'aes_key' => env('wechat.open_platform.aes_key'),
    ],
    
    // 企业微信配置
    'work' => [
        'corp_id' => env('wechat.work.corp_id'),
        'agent_id' => env('wechat.work.agent_id'),
        'secret' => env('wechat.work.secret'),
        'token' => env('wechat.work.token'),
        'aes_key' => env('wechat.work.aes_key'),
    ],
    
    // 日志配置
    'log' => [
        'level' => env('wechat.log.level', 'debug'),
        'file' => runtime_path() . '/logs/wechat.log',
    ],
    
    // 缓存配置
    'cache' => [
        'driver' => env('wechat.cache.driver', 'redis'),
        'prefix' => env('wechat.cache.prefix', 'wechat_'),
    ],
    
    // HTTP 配置
    'http' => [
        'timeout' => env('wechat.http.timeout', 5.0),
        'retry' => env('wechat.http.retry', 1),
        'retry_delay' => env('wechat.http.retry_delay', 500),
    ],
]; 