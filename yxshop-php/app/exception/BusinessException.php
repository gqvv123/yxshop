<?php

namespace app\exception;

use RuntimeException;

/**
 * 组件：Business Exception。
 *
 * @package app\exception
 */
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
