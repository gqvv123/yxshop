<?php

namespace app\middleware;

use app\model\Admin;

class AdminAuthMiddleware extends JwtAuthMiddleware
{
    protected string $modelClass = Admin::class;
    protected string $requestAttr = 'admin';
    protected array $allowedRoles = [];
    protected bool $checkRoles = false;
}
