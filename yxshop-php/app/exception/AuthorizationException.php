<?php

namespace app\exception;

use RuntimeException;

class AuthorizationException extends RuntimeException
{
    public function __construct(string $message = '权限不足', int $code = 403, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
