<?php

namespace app\Enums;

enum PaymentStatus: int
{
    case UNPAID = 10;
    case PAID = 20;
    case REFUNDED = 30;
    case FAILED = 40;

    public function label(): string
    {
        return match ($this) {
            self::UNPAID => '待支付',
            self::PAID => '已支付',
            self::REFUNDED => '已退款',
            self::FAILED => '支付失败',
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
