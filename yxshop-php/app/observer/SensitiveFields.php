<?php

namespace app\observer;

class SensitiveFields
{
    public static function getMaskedFields(string $tableName): array
    {
        return match ($tableName) {
            'yxshop_users' => ['password', 'openid', 'unionid', 'money', 'commission', 'freeze_money', 'frozen_commission'],
            'yxshop_admins' => ['password'],
            'yxshop_orders' => [],
            'yxshop_order_items' => [],
            'yxshop_items' => [],
            'yxshop_after_sales' => [],
            'yxshop_promotions' => [],
            'yxshop_settings' => ['value'],
            default => ['password', 'secret', 'token', 'api_key', 'access_token', 'private_key'],
        };
    }

    public static function getExcludedFields(string $tableName): array
    {
        return match ($tableName) {
            'yxshop_orders' => ['updated_at'],
            'yxshop_order_items' => ['updated_at'],
            'yxshop_items' => ['updated_at', 'click', 'total_sales', 'comment_count'],
            'yxshop_users' => ['updated_at', 'last_login_at', 'last_login_ip', 'login_count'],
            'yxshop_admins' => ['updated_at', 'last_login_at'],
            'yxshop_after_sales' => ['updated_at'],
            'yxshop_promotions' => ['updated_at'],
            'yxshop_settings' => ['updated_at'],
            default => ['updated_at'],
        };
    }

    public static function maskValue(string $field, $value, string $tableName): string
    {
        $maskedFields = static::getMaskedFields($tableName);
        if (in_array($field, $maskedFields)) {
            return '***';
        }
        return $value ?? '';
    }
}