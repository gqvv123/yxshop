<?php

namespace app\Enums;

enum ReceiptStatus: int
{
    case UNRECEIVED = 10;
    case RECEIVED = 20;

    public function label(): string
    {
        return match ($this) {
            self::UNRECEIVED => '未收货',
            self::RECEIVED => '已收货',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
