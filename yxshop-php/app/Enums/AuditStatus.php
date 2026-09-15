<?php

namespace app\Enums;

enum AuditStatus: int
{
    case PENDING = 0;
    case APPROVED = 1;
    case REJECTED = 2;
    case CANCELLED = 3;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待审核',
            self::APPROVED => '已通过',
            self::REJECTED => '已拒绝',
            self::CANCELLED => '已取消',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
