<?php

namespace app\Enums;

enum AfterSalesStatus: int
{
    case PENDING = 10;
    case APPROVED = 20;
    case REJECTED = 30;
    case COMPLETED = 40;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待审核',
            self::APPROVED => '审核通过',
            self::REJECTED => '审核拒绝',
            self::COMPLETED => '已完成',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}