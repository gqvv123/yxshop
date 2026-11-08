<?php

/**
 * AI 模块配置
 *
 * 支持 Anthropic (Claude) 和 OpenAI 兼容模式
 * 配置优先级：数据库 yxshop_settings (group=ai) > .env > 默认值
 *
 * 后台运营人员可通过 SettingController 动态修改 AI 配置（API Key、模型、计费等）
 * 开发者可通过 .env 预填默认值
 */

return [
    // 默认服务商：openai | anthropic
    'default_provider' => getenv('AI_DEFAULT_PROVIDER') ?: 'openai',

    // OpenAI 兼容模式配置（支持 OpenAI 官方、Azure OpenAI、第三方兼容 API）
    'openai' => [
        // API Key（从 .env 或数据库 ai_openai_api_key 读取）
        'api_key'        => getenv('AI_OPENAI_API_KEY') ?: '',
        // 接口地址（可改为代理地址或 Azure endpoint）
        'base_url'       => getenv('AI_OPENAI_BASE_URL') ?: 'https://api.openai.com/v1',
        // 默认模型
        'default_model'  => getenv('AI_OPENAI_MODEL') ?: 'gpt-4o',
        // 超时秒数
        'timeout'        => (int) (getenv('AI_OPENAI_TIMEOUT') ?: 60),
        // 默认最大 tokens
        'max_tokens'     => (int) (getenv('AI_OPENAI_MAX_TOKENS') ?: 4096),
        // 默认温度
        'temperature'    => (float) (getenv('AI_OPENAI_TEMPERATURE') ?: 0.7),
    ],

    // Anthropic (Claude) 配置
    'anthropic' => [
        // API Key
        'api_key'        => getenv('AI_ANTHROPIC_API_KEY') ?: '',
        // 接口地址
        'base_url'       => getenv('AI_ANTHROPIC_BASE_URL') ?: 'https://api.anthropic.com',
        // 默认模型
        'default_model'  => getenv('AI_ANTHROPIC_MODEL') ?: 'claude-sonnet-4-20250514',
        // API 版本
        'api_version'    => getenv('AI_ANTHROPIC_API_VERSION') ?: '2023-06-01',
        // 超时秒数
        'timeout'        => (int) (getenv('AI_ANTHROPIC_TIMEOUT') ?: 60),
        // 默认最大 tokens
        'max_tokens'     => (int) (getenv('AI_ANTHROPIC_MAX_TOKENS') ?: 4096),
        // 默认温度
        'temperature'    => (float) (getenv('AI_ANTHROPIC_TEMPERATURE') ?: 0.7),
    ],

    // 算力计费配置
    'billing' => [
        // 是否启用算力计费（false 时所有操作免费）
        'enabled'        => getenv('AI_BILLING_ENABLED') !== 'false',
        // 新商家默认赠送算力额度（次数）
        'free_quota'     => (int) (getenv('AI_FREE_QUOTA') ?: 50),
        // 算力包套餐配置（后台可修改）
        'packages'       => [
            [
                'name'        => '体验包',
                'calls'       => 100,
                'price'       => 29.00,
                'description' => '适合个人商家试用',
            ],
            [
                'name'        => '标准包',
                'calls'       => 500,
                'price'       => 99.00,
                'description' => '适合中小商家日常使用',
            ],
            [
                'name'        => '专业包',
                'calls'       => 2000,
                'price'       => 299.00,
                'description' => '适合大型商家高频使用',
            ],
            [
                'name'        => '旗舰包',
                'calls'       => 10000,
                'price'       => 999.00,
                'description' => '无限畅享 AI 能力',
            ],
        ],
    ],

    // 各功能单次消耗算力（次）
    'cost_per_call' => [
        'copywriting'    => 1,   // AI 商品文案生成
        'customer_service' => 1, // AI 智能客服
        'analytics'      => 3,   // AI 数据分析（消耗更大）
    ],

    // 请求重试配置
    'retry' => [
        'max_attempts'   => 3,
        'delay_ms'       => 1000,
    ],

    // 敏感字段（写入日志时脱敏）
    'sensitive_fields' => [
        'api_key', 'authorization', 'x-api-key',
    ],
];
