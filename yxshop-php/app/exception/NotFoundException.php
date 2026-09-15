<?php

namespace app\exception;

use RuntimeException;

class NotFoundException extends RuntimeException
{
    public function __construct(string $message = '资源不存在', int $code = 404, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
