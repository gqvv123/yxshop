<?php

namespace app\exception;

use RuntimeException;

class ValidationException extends RuntimeException
{
    protected $errors;

    public function __construct(string $message = '参数验证失败', array $errors = [], int $code = 422, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
