<?php

/**
 * 全局辅助函数：date 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('generate_order_no')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::generateOrderNo() 替代
     */
    function generate_order_no($prefix = 'YX'): string
    {
        return \app\utility\StringHelper::generateOrderNo($prefix);
    }
}

if (!function_exists('generateOrderCode')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::generateOrderCode() 替代
     */
    function generateOrderCode($length = 6): string
    {
        return \app\utility\StringHelper::generateOrderCode();
    }
}

if (!function_exists('get_date_time')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::getDateTime() 替代
     */
    function get_date_time($date, $format = 'Y-m-d h:i A'): string
    {
        return \app\utility\DateHelper::getDateTime($date, $format);
    }
}

if (!function_exists('days_in_year')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::daysInYear() 替代
     */
    function days_in_year($year = null): int
    {
        return \app\utility\DateHelper::daysInYear($year);
    }
}

if (!function_exists('days_in_month')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::daysInMonth() 替代
     */
    function days_in_month($month = null, $year = null): int
    {
        return \app\utility\DateHelper::daysInMonth($month, $year);
    }
}

if (!function_exists('distanceInWords')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::distanceInWords() 替代
     */
    function distanceInWords($distance): string
    {
        return \app\utility\DateHelper::distanceInWords((int)$distance);
    }
}

if (!function_exists('calculateDistance')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::calculateDistance() 替代
     */
    function calculateDistance($formAddress, $toAddress): float
    {
        return \app\utility\DateHelper::calculateDistance(
            (float)($formAddress['lat'] ?? 0),
            (float)($formAddress['lng'] ?? 0),
            (float)($toAddress['lat'] ?? 0),
            (float)($toAddress['lng'] ?? 0)
        );
    }
}
