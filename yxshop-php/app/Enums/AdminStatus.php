<?php

namespace app\Enums;

enum AdminStatus: int
{
    case DISABLED = 0;
    case ACTIVE = 1;

    public function label(): string
    {
        return match ($this) {
            self::DISABLED => '禁用',
            self::ACTIVE => '启用',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}