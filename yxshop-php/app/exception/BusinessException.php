<?php

namespace app\exception;

use RuntimeException;

class BusinessException extends RuntimeException
{
    protected $data;

    public function __construct(string $message = '', int $code = 1, $data = null, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->data = $data;
    }

    public function getData()
    {
        return $this->data;
    }
}
