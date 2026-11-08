<?php

// 从环境变量读取 CORS 白名单；开发环境兜底 localhost
$envOrigins = env('CORS_ORIGINS');
$adminOrigins = $envOrigins
    ? array_map('trim', explode(',', $envOrigins))
    : ['http://localhost:5173', 'http://localhost:3000'];

return [
    'admin_allowed_origins' => $adminOrigins,
];
