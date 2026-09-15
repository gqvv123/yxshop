<?php

namespace app\Enums;

enum RefundStatus: int
{
    case NONE = 10;
    case REFUNDING = 20;
    case REFUNDED = 30;

    public function label(): string
    {
        return match ($this) {
            self::NONE => '无退款',
            self::REFUNDING => '退款中',
            self::REFUNDED => '已退款',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
