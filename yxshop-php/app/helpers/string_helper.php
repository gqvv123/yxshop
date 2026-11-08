<?php

/**
 * 全局辅助函数：string 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('toUnderScore')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::toUnderScore() 替代
     */
    function toUnderScore($str): string
    {
        return \app\utility\StringHelper::toUnderScore($str);
    }
}

if (!function_exists('random_string')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::randomString() 替代
     */
    function random_string($length = 10, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'): string
    {
        return \app\utility\StringHelper::randomString($length);
    }
}

if (!function_exists('hexa_to_rgba')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::hexaToRgba() 替代
     */
    function hexa_to_rgba($code): string
    {
        return \app\utility\StringHelper::hexaToRgba($code);
    }
}

if (!function_exists('trx_number')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::trxNumber() 替代
     */
    function trx_number($length = 14): string
    {
        return \app\utility\StringHelper::trxNumber($length);
    }
}

if (!function_exists('text_sorted')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::textSorted() 替代
     */
    function text_sorted($text): string
    {
        return \app\utility\StringHelper::textSorted($text);
    }
}

if (!function_exists('limit_lines')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::limitLines() 替代
     */
    function limit_lines($text, $limit, $end = '...'): string
    {
        return \app\utility\StringHelper::limitLines($text, $limit, $end);
    }
}

if (!function_exists('limit_words')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::limitWords() 替代
     */
    function limit_words($text, $limit, $end = '...'): string
    {
        return \app\utility\StringHelper::limitWords($text, $limit, $end);
    }
}

if (!function_exists('replace_sort_code')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::replaceSortCode() 替代
     */
    function replace_sort_code($message, $name = null, $replaceableCode = []): string
    {
        return \app\utility\StringHelper::replaceSortCode($message);
    }
}

if (!function_exists('rand_token')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::randToken() 替代
     */
    function rand_token($length = 10): string
    {
        return \app\utility\StringHelper::randToken($length);
    }
}

if (!function_exists('str_unique')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::strUnique() 替代
     */
    function str_unique(int $length = 16): string
    {
        return \app\utility\StringHelper::strUnique($length);
    }
}

if (!function_exists('unslug')) {
    /**
     * @deprecated 使用 \app\utility\StringHelper::unslug() 替代
     */
    function unslug($slug, $delimiter = '-'): string
    {
        return \app\utility\StringHelper::unslug($slug, $delimiter);
    }
}

if (!function_exists('diff_for_humans')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::diffForHumans() 替代
     */
    function diff_for_humans($date): string
    {
        return \app\utility\DateHelper::diffForHumans($date);
    }
}

if (!function_exists('sort_by_month')) {
    /**
     * @deprecated 使用 \app\utility\DateHelper::sortByMonth() 替代
     */
    function sort_by_month(array $data, ?array $keys = null): array
    {
        return \app\utility\DateHelper::sortByMonth($data);
    }
}
