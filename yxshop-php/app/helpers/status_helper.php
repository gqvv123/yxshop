<?php

/**
 * 全局辅助函数：status 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('order_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::orderStatus() 替代
     */
    function order_status(int|string $status): string
    {
        return \app\utility\OrderHelper::orderStatus($status);
    }
}

if (!function_exists('order_status_badge')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::orderStatusBadge() 替代
     */
    function order_status_badge(int|string $status): array
    {
        return \app\utility\OrderHelper::orderStatusBadge($status);
    }
}

if (!function_exists('delivery_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::deliveryStatus() 替代
     */
    function delivery_status(int|string $status): string
    {
        return \app\utility\OrderHelper::deliveryStatus($status);
    }
}

if (!function_exists('order_payment_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::orderPaymentStatus() 替代
     */
    function order_payment_status(int|string $paymentStatus): string
    {
        return \app\utility\OrderHelper::orderPaymentStatus($paymentStatus);
    }
}

if (!function_exists('product_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::productStatus() 替代
     */
    function product_status(int|string $status): string
    {
        return \app\utility\OrderHelper::productStatus($status);
    }
}

if (!function_exists('reward_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::rewardStatus() 替代
     */
    function reward_status(int|string $status): string
    {
        return \app\utility\OrderHelper::rewardStatus($status);
    }
}

if (!function_exists('show_ratings')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::showRatings() 替代
     */
    function show_ratings($ratings): string
    {
        return \app\utility\OrderHelper::showRatings($ratings);
    }
}

if (!function_exists('response_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::responseStatus() 替代
     */
    function response_status(string $message = 'Successfully Completed', string $key = 'success'): array
    {
        return \app\utility\OrderHelper::responseStatus($key === 'success' ? 1 : 0);
    }
}

if (!function_exists('update_status')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::updateStatus() 替代
     */
    function update_status($id, $modelName, $status, $columName = 'status'): string
    {
        return \app\utility\OrderHelper::updateStatus($status);
    }
}

if (!function_exists('mark_status_update')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::markStatusUpdate() 替代
     */
    function mark_status_update($modelName, $status, $column, $ids): string
    {
        return \app\utility\OrderHelper::markStatusUpdate($status);
    }
}

if (!function_exists('validateModelStatus')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::validateModelStatus() 替代
     */
    function validateModelStatus($request, array $modelInfo): bool
    {
        return \app\utility\OrderHelper::validateModelStatus($modelInfo['status'] ?? 0, $modelInfo['valid_statuses'] ?? [0, 1]);
    }
}
