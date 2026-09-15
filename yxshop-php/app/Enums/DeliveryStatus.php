<?php

namespace app\Enums;

enum DeliveryStatus: int
{
    case UNSHIPPED = 10;
    case SHIPPED = 20;

    public function label(): string
    {
        return match ($this) {
            self::UNSHIPPED => '未发货',
            self::SHIPPED => '已发货',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
