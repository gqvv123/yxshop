<?php

namespace app\exception;

use RuntimeException;

class AuthenticationException extends RuntimeException
{
    public function __construct(string $message = '未授权访问，请先登录', int $code = 401, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
