<?php

return [
    // JWT密钥
    'secret' => env('JWT_SECRET', ''),
    
    // JWT算法
    'algorithm' => env('jwt.algorithm', 'HS256'),
    
    // 令牌过期时间（秒）
    'expire_time' => env('jwt.expire_time', 7200), // 2小时
    
    // 刷新令牌过期时间（秒）
    'refresh_expire_time' => env('jwt.refresh_expire_time', 604800), // 7天
    
    // 令牌黑名单缓存时间（秒）
    'blacklist_cache_time' => env('jwt.blacklist_cache_time', 86400), // 24小时
]; 