<?php

use app\common\StatusEnum;

/**
 * 全局辅助函数：auth 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('yxmall_pass')) {
    /**
     * 生成密码hash值
     */
    function yxmall_pass($password): string
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }
}

if (!function_exists('yxmall_pass_verify')) {
    /**
     * 验证密码（兼容旧版 md5 双重加密）
     */
    function yxmall_pass_verify($password, $hash): bool
    {
        if (password_verify($password, $hash)) {
            return true;
        }
        return md5(md5($password) . 'yxmall_ok') === $hash;
    }
}

if (!function_exists('yxmall_pass_needs_rehash')) {
    /**
     * 检查密码是否需要重新hash
     */
    function yxmall_pass_needs_rehash($hash): bool
    {
        if (str_starts_with($hash, '$2y$') || str_starts_with($hash, '$2a$') || str_starts_with($hash, '$2b$')) {
            return password_needs_rehash($hash, PASSWORD_DEFAULT);
        }
        return true;
    }
}

if (!function_exists('auth_user')) {
    /**
     * 获取当前认证用户（管理员）
     */
    function auth_user(): ?object
    {
        $admin = session()->get('admin');
        if ($admin && is_array($admin)) {
            return (object)$admin;
        }
        return null;
    }
}

if (!function_exists('auth_check')) {
    /**
     * 检查用户是否已认证
     */
    function auth_check(): bool
    {
        return session()->has('admin_id') && session()->has('admin');
    }
}

if (!function_exists('auth_id')) {
    /**
     * 获取认证用户ID
     */
    function auth_id(): mixed
    {
        return session()->get('admin_id');
    }
}

if (!function_exists('csrf_token')) {
    /**
     * 获取/生成 CSRF Token
     */
    function csrf_token(): string
    {
        $token = session('_token');
        if (!$token) {
            try {
                $token = bin2hex(random_bytes(32));
            } catch (\Exception $e) {
                $token = md5(uniqid('', true));
            }
            session(['_token' => $token]);
        }
        return (string)$token;
    }
}

if (!function_exists('csrf_field')) {
    /**
     * 生成隐藏域 CSRF 字段
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('permission_check')) {
    /**
     * 权限检查
     */
    function permission_check($permission): bool
    {
        $admin = session('admin');
        if (!$admin) {
            return false;
        }
        if (isset($admin['is_super_admin']) && $admin['is_super_admin']) {
            return true;
        }
        if (!isset($admin['role'])) {
            return true;
        }
        $permissions = format_permissions(json_decode($admin['role']['permissions'] ?? '[]', true));
        return in_array($permission, $permissions);
    }
}

if (!function_exists('format_permissions')) {
    /**
     * 格式化权限数组
     */
    function format_permissions($permissions): array
    {
        $permission_values = [];
        foreach ($permissions as $features) {
            foreach ($features as $feature) {
                $permission_values = array_merge($permission_values, $feature);
            }
        }
        return $permission_values;
    }
}

if (!function_exists('is_demo')) {
    /**
     * 检查是否为演示模式
     */
    function is_demo(): bool
    {
        return strtolower(env('APP_MODE')) === 'demo';
    }
}

if (!function_exists('guest_checkout')) {
    /**
     * 检查是否允许游客结账
     */
    function guest_checkout(): bool
    {
        return site_settings('guest_checkout') == StatusEnum::true->status();
    }
}
