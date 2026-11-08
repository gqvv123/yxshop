<?php

namespace app\service\ai;

/**
 * AI Provider 接口
 *
 * 統一定義 OpenAI / Anthropic 等 AI 服务商的調用契約
 */
interface AiProviderInterface
{
    /**
     * 发送聊天请求
     *
     * @param array $messages  对话消息 [{role: 'system'|'user'|'assistant', content: '...'}]
     * @param array $options   可选参数 [model, max_tokens, temperature, system_prompt]
     * @return AiResponse
     */
    public function chat(array $messages, array $options = []): AiResponse;

    /**
     * 获取 Provider 名称
     */
    public function getName(): string;

    /**
     * 检查 Provider 是否已配置可用
     */
    public function isConfigured(): bool;
}
