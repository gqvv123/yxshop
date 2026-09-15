<?php

namespace app\Enums;

enum CouponType: int
{
    case MONEY_OFF = 10;
    case DISCOUNT = 20;

    public function label(): string
    {
        return match ($this) {
            self::MONEY_OFF => '满减券',
            self::DISCOUNT => '折扣券',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function fromValue(int $value): ?self
    {
        return self::tryFrom($value);
    }
}
