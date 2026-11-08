<?php

namespace app\common;

/**
 * 公共组件：通用状态枚举。
 *
 * @package app\common
 */

enum StatusEnum: int
{
    case true = 1;
    case false = 0;

    public function status(): int
    {
        return $this->value;
    }

    public static function fromValue(int $value): self
    {
        return match($value) {
            1 => self::true,
            0 => self::false,
            default => self::false,
        };
    }
}

