<?php

namespace app\utility;

use support\Response;

class ApiJsonResponse
{
    protected int $httpCode = 200;
    protected int $code = 0;
    protected string $message = '';
    protected mixed $data;
    protected array $headers = [];

    public function __construct(mixed $data)
    {
        $this->data = $data;
    }

    public function success(string $message = 'success', int $httpCode = 200, ?int $code = null): Response
    {
        $this->httpCode = $httpCode;
        $this->code = $code ?? 0;
        $this->message = $message;

        $response = [
            'code' => $this->code,
            'message' => $message,
            'data' => $this->data,
        ];

        return new Response($this->httpCode, array_merge(['Content-Type' => 'application/json'], $this->headers),
            json_encode($response, JSON_UNESCAPED_UNICODE));
    }

    public function fails(string $message = 'error', int $httpCode = 400, ?int $code = null): Response
    {
        $this->httpCode = $httpCode;
        $this->code = $code ?? 1;
        $this->message = $message;

        $response = [
            'code' => $this->code,
            'message' => $message,
            'data' => empty($this->data) ? null : $this->data,
        ];

        return new Response($this->httpCode, array_merge(['Content-Type' => 'application/json'], $this->headers),
            json_encode($response, JSON_UNESCAPED_UNICODE));
    }
}
