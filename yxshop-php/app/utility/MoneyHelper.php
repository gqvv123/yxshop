<?php

namespace app\utility;

use app\common\StatusEnum;
use app\service\cache\TieredCache;
use Illuminate\Database\Eloquent\Builder;

class MoneyHelper
{
    public static function formatMoney(float $amount, int $decimal = 2): string
    {
        return number_format($amount, $decimal);
    }

    public static function shortAmount(mixed $amount, bool $showCurrency = true, bool $numberFormat = true): string
    {
        if ($amount === null) {
            $amount = 0;
        }
        $decimalDigit = site_settings('digit_after_decimal', 2);
        $currency = session()->get('web_currency');
        if ($currency) {
            $amount *= $currency->rate;
        }
        if ($numberFormat) {
            $formattedAmount = number_format($amount, $decimalDigit);
        } else {
            $formattedAmount = round((float)$amount, $decimalDigit);
        }
        if ($showCurrency && $currency) {
            $position = site_settings('currency_position', StatusEnum::true->status());
            if ($position == StatusEnum::true->status()) {
                $formattedAmount = $currency->symbol . $formattedAmount;
            } else {
                $formattedAmount = $formattedAmount . $currency->symbol;
            }
        }
        return $formattedAmount;
    }

    public static function showAmount(mixed $amount, ?string $symbol = null): string
    {
        if ($amount === null) {
            $amount = 0;
        }
        $decimalDigit = site_settings('digit_after_decimal', 2);
        $currency = session()->get('web_currency');
        $formattedAmount = number_format($amount, $decimalDigit);
        $position = site_settings('currency_position', StatusEnum::true->status());
        if ($position == StatusEnum::true->status()) {
            $formattedAmount = ($symbol ?? ($currency ? $currency->symbol : '')) . $formattedAmount;
        } else {
            $formattedAmount = $formattedAmount . ($symbol ?? ($currency ? $currency->symbol : ''));
        }
        return $formattedAmount;
    }

    public static function apiShortAmount(int|float $amount, int $length = 2): int|float
    {
        $currency = TieredCache::get('api_currency');
        if ($currency) {
            $amount *= $currency->rate;
        }
        $decimalDigit = site_settings('digit_after_decimal', 2);
        return round($amount, $decimalDigit);
    }

    public static function convertToBase(float $amount, int $length = 2): int
    {
        $currency = session()->get('web_currency');
        $amountInUsd = $amount / (float)$currency->rate;
        return (int)round($amountInUsd);
    }

    public static function exchangeRate(mixed $currency): int|float
    {
        $base = default_currency();
        $amount = $base->rate;
        try {
            $baseCurrency = session()->get('web_currency') ?? $base;
            $exchangeRate = $baseCurrency->rate / ($currency ? $currency->rate : $baseCurrency->rate);
            $amount = 1 / $exchangeRate;
        } catch (\Throwable $th) {
        }
        return round($amount);
    }

    public static function defaultCurrencyConverter(int|float $amount, $currency): int|float
    {
        $amountInUsd = $amount / $currency->rate;
        return $amountInUsd * default_currency()->rate;
    }

    public static function calDiscount(float $total, float $discount, int $type = 0): float
    {
        if ($type == 0) {
            return $total - $discount;
        }
        return $total - ($total * $discount / 100);
    }

    public static function discount(float $total, float $discount, int $type = 0): float
    {
        return static::calDiscount($total, $discount, $type);
    }

    public static function getCurrencySymbol(): string
    {
        $currency = TieredCache::remember('currency', 24 * 60 * 60, function () {
            return \app\model\Currency::where(function (Builder $query) {
                return $query->where('id', @session()->get('web_currency')->id)
                    ->orwhere('default', StatusEnum::true->status());
            })->first();
        });
        return $currency ? $currency->symbol : "$";
    }

    public static function getCurrencyName(): string
    {
        $currency = TieredCache::remember('currency_name', 24 * 60 * 60, function () {
            return \app\model\Currency::where(function (Builder $query) {
                return $query->where('id', @session()->get('web_currency')->id)
                    ->orwhere('default', StatusEnum::true->status());
            })->first();
        });
        return $currency ? $currency->name : "USD";
    }

    public static function defaultCurrency()
    {
        return TieredCache::remember('default_currency', 24 * 60 * 60, function () {
            return \app\model\Currency::where('is_default', StatusEnum::true->status())->first();
        });
    }

    public static function showCurrency(): string
    {
        return @session()->get('web_currency')->symbol ?? static::getCurrencySymbol();
    }
}
