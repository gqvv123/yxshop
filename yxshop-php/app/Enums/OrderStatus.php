<?php

namespace app\Enums;

enum OrderStatus: int
{
    case PENDING = 10;
    case PAID = 20;
    case SHIPPED = 30;
    case COMPLETED = 40;
    case CANCELLED = 50;
    case REFUNDED = 60;

    public function label(): string
    {
        return match ($this) {
            self::PENDING => '待付款',
            self::PAID => '待发货',
            self::SHIPPED => '待收货',
            self::COMPLETED => '已完成',
            self::CANCELLED => '已取消',
            self::REFUNDED => '已退款',
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
