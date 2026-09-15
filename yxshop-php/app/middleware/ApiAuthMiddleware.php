<?php

namespace app\middleware;

use app\model\User;

class ApiAuthMiddleware extends JwtAuthMiddleware
{
    protected string $modelClass = User::class;
    protected string $requestAttr = 'user';
    // User 模型无 role/type 字段，普通用户认证不卡角色
    protected array $allowedRoles = [];
    protected bool $checkRoles = false;
}
