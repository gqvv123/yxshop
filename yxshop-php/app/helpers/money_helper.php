<?php

/**
 * 全局辅助函数：money 相关。
 *
 * 从 app/functions.php 按主题拆分而来，函数实现保持原样。
 *
 * @package app\helpers
 */

if (!function_exists('format_money')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::formatMoney() 替代
     */
    function format_money($amount, $decimals = 2): string
    {
        return \app\utility\MoneyHelper::formatMoney($amount, $decimals);
    }
}

if (!function_exists('short_amount')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::shortAmount() 替代
     */
    function short_amount(mixed $amount, bool $showCurrency = true, bool $number_format = true): string
    {
        return \app\utility\MoneyHelper::shortAmount($amount, $showCurrency, $number_format);
    }
}

if (!function_exists('show_amount')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::showAmount() 替代
     */
    function show_amount(mixed $amount, ?string $symbol = null): string
    {
        return \app\utility\MoneyHelper::showAmount($amount, $symbol);
    }
}

if (!function_exists('api_short_amount')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::apiShortAmount() 替代
     */
    function api_short_amount(int|float $amount, int $length = 2): int|float
    {
        return \app\utility\MoneyHelper::apiShortAmount($amount, $length);
    }
}

if (!function_exists('convert_to_base')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::convertToBase() 替代
     */
    function convert_to_base($amount, $length = 2): int
    {
        return \app\utility\MoneyHelper::convertToBase($amount, $length);
    }
}

if (!function_exists('exchange_rate')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::exchangeRate() 替代
     */
    function exchange_rate(mixed $currency): int|float
    {
        return \app\utility\MoneyHelper::exchangeRate($currency);
    }
}

if (!function_exists('default_currency_converter')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::defaultCurrencyConverter() 替代
     */
    function default_currency_converter(int|float $amount, $currency): int|float
    {
        return \app\utility\MoneyHelper::defaultCurrencyConverter($amount, $currency);
    }
}

if (!function_exists('get_currency_symbol')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::getCurrencySymbol() 替代
     */
    function get_currency_symbol(): string
    {
        return \app\utility\MoneyHelper::getCurrencySymbol();
    }
}

if (!function_exists('get_currency_name')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::getCurrencyName() 替代
     */
    function get_currency_name(): string
    {
        return \app\utility\MoneyHelper::getCurrencyName();
    }
}

if (!function_exists('show_currency')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::showCurrency() 替代
     */
    function show_currency(): string
    {
        return \app\utility\MoneyHelper::showCurrency();
    }
}

if (!function_exists('discount')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::discount() 替代
     */
    function discount($total, $discount, $type = 0): float
    {
        return \app\utility\MoneyHelper::discount($total, $discount, $type);
    }
}

if (!function_exists('cal_discount')) {
    /**
     * @deprecated 使用 \app\utility\MoneyHelper::calDiscount() 替代
     */
    function cal_discount($discount, $price): float
    {
        return \app\utility\MoneyHelper::calDiscount($discount, $price);
    }
}

if (!function_exists('negative_value')) {
    /**
     * @deprecated 使用 \app\utility\OrderHelper::negativeValue() 替代
     */
    function negative_value(int|float $value, $float = false): string
    {
        return \app\utility\OrderHelper::negativeValue($value);
    }
}
