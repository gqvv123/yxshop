<?php
// 清除首页 Redis 缓存
try {
    $r = new Redis();
    $r->connect('127.0.0.1', 6379);
    $keys = ['mini_page:home:1', 'mini_page:home:10001'];
    foreach ($keys as $key) {
        $deleted = $r->del($key);
        echo "DEL {$key}: {$deleted}\n";
    }
    echo "Cache cleared!\n";
} catch (Exception $e) {
    echo 'Redis error: ' . $e->getMessage() . "\n";
}
