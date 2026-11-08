<?php

namespace app\exception;

use RuntimeException;

/**
 * 组件：Authorization Exception。
 *
 * @package app\exception
 */
class AuthorizationException extends RuntimeException
{
    public function __construct(string $message = '权限不足', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
