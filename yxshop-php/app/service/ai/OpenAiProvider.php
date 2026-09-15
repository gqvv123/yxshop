<?php

namespace app\service\ai;

use GuzzleHttp\Client;
use app\service\SettingService;
use app\exception\BusinessException;

/**
 * OpenAI 兼容模式 Provider
 *
 * 支持：
 * - OpenAI 官方 API (https://api.openai.com/v1)
 * - Azure OpenAI
 * - 第三方兼容 API (如 OneAPI、DeepSeek、月之暗面等)
 *
 * 配置来源：数据库 yxshop_settings (group=ai) → .env → config/ai.php 默认值
 */
class OpenAiProvider implements AiProviderInterface
{
    private Client $httpClient;
    private SettingService $settingService;

    // OpenAI 模型定价（USD / 1K tokens），用于费用估算
    private const MODEL_PRICING = [
        'gpt-4o'       => ['input' => 0.0025,  'output' => 0.01],
        'gpt-4o-mini'  => ['input' => 0.00015, 'output' => 0.0006],
        'gpt-4-turbo'  => ['input' => 0.01,    'output' => 0.03],
        'gpt-4'        => ['input' => 0.03,    'output' => 0.06],
        'gpt-3.5-turbo'=> ['input' => 0.0005,  'output' => 0.0015],
    ];

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => $this->getConfig('timeout', 60),
            'connect_timeout' => 10,
        ]);
        $this->settingService = new SettingService();
    }

    public function getName(): string
    {
        return 'openai';
    }

    /**
     * 从数据库/配置中读取参数
     */
    private function getConfig(string $key, $default = null)
    {
        // 优先数据库
        $dbKey = 'ai_openai_' . $key;
        $dbVal = $this->settingService->getRawSetting($dbKey, 0, null);
        if ($dbVal !== null && $dbVal !== '') {
            return $dbVal;
        }
        // 回退 config/ai.php
        $config = config('ai.openai', []);
        return $config[$key] ?? $default;
    }

    /**
     * 获取 API Key（从数据库加密存储中读取）
     */
    private function getApiKey(): string
    {
        $key = $this->settingService->getRawSetting('ai_openai_api_key', 0, '');
        if (empty($key)) {
            $key = getenv('AI_OPENAI_API_KEY') ?: '';
        }
        return $key;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getApiKey());
    }

    public function chat(array $messages, array $options = []): AiResponse
    {
        $apiKey   = $this->getApiKey();
        if (empty($apiKey)) {
            throw new BusinessException('OpenAI API Key 未配置，请在后台 AI 设置中填写', 400);
        }

        $baseUrl  = rtrim($this->getConfig('base_url', 'https://api.openai.com/v1'), '/');
        $model    = $options['model']       ?? $this->getConfig('default_model', 'gpt-4o');
        $maxTokens= $options['max_tokens']  ?? $this->getConfig('max_tokens', 4096);
        $temp     = $options['temperature'] ?? $this->getConfig('temperature', 0.7);

        // 如果有 system_prompt，插入到消息数组开头
        if (!empty($options['system_prompt'])) {
            array_unshift($messages, [
                'role'    => 'system',
                'content' => $options['system_prompt'],
            ]);
        }

        $startMs = microtime(true);

        try {
            $response = $this->httpClient->post($baseUrl . '/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type'  => 'application/json',
                ],
                'json' => [
                    'model'       => $model,
                    'messages'    => $messages,
                    'max_tokens'  => (int) $maxTokens,
                    'temperature' => (float) $temp,
                ],
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $durationMs = (int) ((microtime(true) - $startMs) * 1000);

            $content = $body['choices'][0]['message']['content'] ?? '';
            $inputTokens  = $body['usage']['prompt_tokens']     ?? 0;
            $outputTokens = $body['usage']['completion_tokens'] ?? 0;
            $actualModel  = $body['model'] ?? $model;

            return new AiResponse([
                'content'       => $content,
                'model'         => $actualModel,
                'input_tokens'  => $inputTokens,
                'output_tokens' => $outputTokens,
                'duration_ms'   => $durationMs,
                'provider'      => $this->getName(),
                'cost_usd'      => $this->estimateCost($actualModel, $inputTokens, $outputTokens),
                'raw'           => $body,
            ]);

        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $errorBody = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : '';
            $errorMsg = $this->parseApiError($errorBody) ?: $e->getMessage();
            throw new BusinessException('OpenAI 调用失败: ' . $errorMsg, $e->getCode() ?: 500);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            throw new BusinessException('OpenAI 服务端错误，请稍后重试', 502);
        } catch (\Exception $e) {
            throw new BusinessException('OpenAI 请求异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 估算费用（USD）
     */
    private function estimateCost(string $model, int $inputTokens, int $outputTokens): ?float
    {
        // 模糊匹配模型名（处理 gpt-4o-2024-08-06 等变体）
        $pricing = null;
        foreach (self::MODEL_PRICING as $name => $rates) {
            if (str_starts_with($model, $name)) {
                $pricing = $rates;
                break;
            }
        }
        if (!$pricing) {
            return null;
        }
        return round(
            ($inputTokens / 1000 * $pricing['input']) + ($outputTokens / 1000 * $pricing['output']),
            6
        );
    }

    /**
     * 解析 API 错误响应
     */
    private function parseApiError(string $errorBody): string
    {
        if (empty($errorBody)) {
            return '';
        }
        $decoded = json_decode($errorBody, true);
        return $decoded['error']['message'] ?? '';
    }
}
