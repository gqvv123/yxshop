<?php

namespace app\service\ai;

use GuzzleHttp\Client;
use app\service\SettingService;
use app\exception\BusinessException;

/**
 * Anthropic (Claude) Provider
 *
 * 调用 Anthropic Messages API
 * 文档: https://docs.anthropic.com/en/api/messages
 *
 * 配置来源：数据库 yxshop_settings (group=ai) → .env → config/ai.php 默认值
 */
class AnthropicProvider implements AiProviderInterface
{
    private Client $httpClient;
    private SettingService $settingService;

    // Claude 模型定价（USD / 1K tokens）
    private const MODEL_PRICING = [
        'claude-sonnet-4-20250514'     => ['input' => 0.003, 'output' => 0.015],
        'claude-3-5-sonnet-20241022'   => ['input' => 0.003, 'output' => 0.015],
        'claude-3-5-sonnet-20240620'   => ['input' => 0.003, 'output' => 0.015],
        'claude-3-5-haiku-20241022'    => ['input' => 0.0008, 'output' => 0.004],
        'claude-3-opus-20240229'       => ['input' => 0.015, 'output' => 0.075],
        'claude-3-haiku-20240307'      => ['input' => 0.00025, 'output' => 0.00125],
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
        return 'anthropic';
    }

    /**
     * 从数据库/配置中读取参数
     */
    private function getConfig(string $key, $default = null)
    {
        $dbKey = 'ai_anthropic_' . $key;
        $dbVal = $this->settingService->getRawSetting($dbKey, 0, null);
        if ($dbVal !== null && $dbVal !== '') {
            return $dbVal;
        }
        $config = config('ai.anthropic', []);
        return $config[$key] ?? $default;
    }

    /**
     * 获取 API Key
     */
    private function getApiKey(): string
    {
        $key = $this->settingService->getRawSetting('ai_anthropic_api_key', 0, '');
        if (empty($key)) {
            $key = getenv('AI_ANTHROPIC_API_KEY') ?: '';
        }
        return $key;
    }

    public function isConfigured(): bool
    {
        return !empty($this->getApiKey());
    }

    public function chat(array $messages, array $options = []): AiResponse
    {
        $apiKey = $this->getApiKey();
        if (empty($apiKey)) {
            throw new BusinessException('Anthropic API Key 未配置，请在后台 AI 设置中填写', 400);
        }

        $baseUrl  = rtrim($this->getConfig('base_url', 'https://api.anthropic.com'), '/');
        $apiVer   = $this->getConfig('api_version', '2023-06-01');
        $model    = $options['model']       ?? $this->getConfig('default_model', 'claude-sonnet-4-20250514');
        $maxTokens= $options['max_tokens']  ?? $this->getConfig('max_tokens', 4096);
        $temp     = $options['temperature'] ?? $this->getConfig('temperature', 0.7);

        // Anthropic 的 system 参数独立于 messages
        $systemPrompt = $options['system_prompt'] ?? '';

        // Anthropic messages 格式转换：
        // 要求 messages 数组的第一个消息 role 为 'user'（system 已独立提取）
        $convertedMessages = [];
        foreach ($messages as $msg) {
            $role = $msg['role'] ?? 'user';
            if ($role === 'system') {
                // 合并到 systemPrompt
                $systemPrompt = ($systemPrompt ? $systemPrompt . "\n" : '') . ($msg['content'] ?? '');
                continue;
            }
            $convertedMessages[] = [
                'role'    => $role,
                'content' => $msg['content'] ?? '',
            ];
        }

        // 确保第一条消息是 user
        if (empty($convertedMessages) || $convertedMessages[0]['role'] !== 'user') {
            $convertedMessages = array_merge([
                ['role' => 'user', 'content' => '你好'],
            ], $convertedMessages);
        }

        $startMs = microtime(true);

        try {
            $payload = [
                'model'       => $model,
                'max_tokens'  => (int) $maxTokens,
                'messages'    => $convertedMessages,
                'temperature' => (float) $temp,
            ];
            if (!empty($systemPrompt)) {
                $payload['system'] = $systemPrompt;
            }

            $response = $this->httpClient->post($baseUrl . '/v1/messages', [
                'headers' => [
                    'x-api-key'         => $apiKey,
                    'anthropic-version' => $apiVer,
                    'Content-Type'      => 'application/json',
                ],
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);
            $durationMs = (int) ((microtime(true) - $startMs) * 1000);

            // Anthropic 返回的 content 是数组，取 text 块
            $content = '';
            if (isset($body['content']) && is_array($body['content'])) {
                foreach ($body['content'] as $block) {
                    if (($block['type'] ?? '') === 'text') {
                        $content .= $block['text'] ?? '';
                    }
                }
            }

            $inputTokens  = $body['usage']['input_tokens']  ?? 0;
            $outputTokens = $body['usage']['output_tokens'] ?? 0;
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
            throw new BusinessException('Anthropic 调用失败: ' . $errorMsg, $e->getCode() ?: 500);
        } catch (\GuzzleHttp\Exception\ServerException $e) {
            throw new BusinessException('Anthropic 服务端错误，请稍后重试', 502);
        } catch (\Exception $e) {
            throw new BusinessException('Anthropic 请求异常: ' . $e->getMessage(), 500);
        }
    }

    /**
     * 估算费用（USD）
     */
    private function estimateCost(string $model, int $inputTokens, int $outputTokens): ?float
    {
        $pricing = self::MODEL_PRICING[$model] ?? null;
        if (!$pricing) {
            // 模糊匹配
            foreach (self::MODEL_PRICING as $name => $rates) {
                if (str_starts_with($model, $name)) {
                    $pricing = $rates;
                    break;
                }
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
        return $decoded['error']['message'] ?? ($decoded['message'] ?? '');
    }
}
