<?php

namespace app\middleware;

use app\model\Admin;

/**
 * 中间件：Admin Auth Middleware。
 *
 * @package app\middleware
 */
class AdminAuthMiddleware extends JwtAuthMiddleware
{
    protected string $modelClass = Admin::class;
    protected string $requestAttr = 'admin';
    protected array $allowedRoles = [];
    protected bool $checkRoles = false;
}
