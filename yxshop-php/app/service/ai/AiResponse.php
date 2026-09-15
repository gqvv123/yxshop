<?php

namespace app\service\ai;

/**
 * AI 响应数据结构
 *
 * 統一封裝不同 AI 服务商的返回结果
 */
class AiResponse
{
    /** @var string AI 返回的文本内容 */
    public string $content;

    /** @var string 使用的模型名称 */
    public string $model;

    /** @var int 输入 token 数 */
    public int $inputTokens;

    /** @var int 输出 token 数 */
    public int $outputTokens;

    /** @var int 请求耗时（毫秒） */
    public int $durationMs;

    /** @var string 服务商名称 */
    public string $provider;

    /** @var float|null 费用估算（USD） */
    public ?float $costUsd;

    /** @var array|null 原始响应（调试用） */
    public ?array $raw;

    public function __construct(array $data = [])
    {
        $this->content      = $data['content']      ?? '';
        $this->model        = $data['model']        ?? '';
        $this->inputTokens  = $data['input_tokens'] ?? 0;
        $this->outputTokens = $data['output_tokens'] ?? 0;
        $this->durationMs   = $data['duration_ms']  ?? 0;
        $this->provider     = $data['provider']     ?? '';
        $this->costUsd      = $data['cost_usd']     ?? null;
        $this->raw          = $data['raw']          ?? null;
    }

    /**
     * 总 token 数
     */
    public function totalTokens(): int
    {
        return $this->inputTokens + $this->outputTokens;
    }

    /**
     * 转为数组
     */
    public function toArray(): array
    {
        return [
            'content'       => $this->content,
            'model'         => $this->model,
            'input_tokens'  => $this->inputTokens,
            'output_tokens' => $this->outputTokens,
            'duration_ms'   => $this->durationMs,
            'provider'      => $this->provider,
            'cost_usd'      => $this->costUsd,
        ];
    }
}
