<?php

namespace app\service\ai;

use app\service\SettingService;
use app\exception\BusinessException;

/**
 * AI 客户端（统一调用入口）
 *
 * 职责：
 * 1. 根据配置自动选择 Provider（OpenAI / Anthropic）
 * 2. 支持指定 Provider 调用
 * 3. 支持自动降级（主服务商不可用时回退到备用）
 * 4. 统一消息格式转换
 *
 * 使用示例：
 *   $client = new AiClient();
 *   $response = $client->chat([
 *       ['role' => 'user', 'content' => '你好']
 *   ], [
 *       'system_prompt' => '你是一个商城客服',
 *       'feature' => 'customer_service',
 *   ]);
 */
class AiClient
{
    private SettingService $settingService;

    /** @var array<string, AiProviderInterface> Provider 实例缓存 */
    private array $providers = [];

    public function __construct()
    {
        $this->settingService = new SettingService();
    }

    /**
     * 获取 Provider 实例
     */
    public function getProvider(?string $name = null): AiProviderInterface
    {
        $name = $name ?: $this->getDefaultProvider();

        if (!isset($this->providers[$name])) {
            $this->providers[$name] = $this->createProvider($name);
        }

        return $this->providers[$name];
    }

    /**
     * 统一聊天接口
     *
     * @param array $messages  消息数组 [{role, content}]
     * @param array $options   [provider, model, system_prompt, max_tokens, temperature, feature]
     * @return AiResponse
     */
    public function chat(array $messages, array $options = []): AiResponse
    {
        $providerName = $options['provider'] ?? null;
        $provider = $this->getProvider($providerName);

        if (!$provider->isConfigured()) {
            // 尝试降级到备用 Provider
            $fallback = $this->getFallbackProvider($providerName ?? $this->getDefaultProvider());
            if ($fallback && $fallback->isConfigured()) {
                $provider = $fallback;
            } else {
                throw new BusinessException(
                    'AI 服务未配置，请在后台「AI 设置」中填写 API Key',
                    400
                );
            }
        }

        return $provider->chat($messages, $options);
    }

    /**
     * 获取默认 Provider 名称
     */
    private function getDefaultProvider(): string
    {
        $dbVal = $this->settingService->getSetting('ai_default_provider', 0, null);
        if ($dbVal) {
            return $dbVal;
        }
        return config('ai.default_provider', 'openai');
    }

    /**
     * 获取备用 Provider（降级用）
     */
    private function getFallbackProvider(string $exclude): ?AiProviderInterface
    {
        $allProviders = ['openai', 'anthropic'];
        foreach ($allProviders as $name) {
            if ($name === $exclude) {
                continue;
            }
            $provider = $this->getProvider($name);
            if ($provider->isConfigured()) {
                return $provider;
            }
        }
        return null;
    }

    /**
     * 创建 Provider 实例
     */
    private function createProvider(string $name): AiProviderInterface
    {
        return match ($name) {
            'openai'    => new OpenAiProvider(),
            'anthropic' => new AnthropicProvider(),
            default     => throw new BusinessException("不支持的 AI 服务商: {$name}", 400),
        };
    }

    /**
     * 获取所有可用 Provider 列表（用于后台显示）
     */
    public function getAvailableProviders(): array
    {
        $list = [];
        foreach (['openai', 'anthropic'] as $name) {
            $provider = $this->getProvider($name);
            $list[] = [
                'name'         => $name,
                'configured'   => $provider->isConfigured(),
                'is_default'   => $name === $this->getDefaultProvider(),
            ];
        }
        return $list;
    }
}
