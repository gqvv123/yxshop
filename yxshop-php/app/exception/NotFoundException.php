<?php

namespace app\exception;

use RuntimeException;

/**
 * 组件：Not Found Exception。
 *
 * @package app\exception
 */
class NotFoundException extends RuntimeException
{
    public function __construct(string $message = '资源不存在', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
