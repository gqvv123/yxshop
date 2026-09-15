<?php

namespace app\service;

use app\service\ai\AiClient;
use app\service\ai\AiResponse;
use app\repository\AiUsageLogRepository;
use app\repository\AiCreditPackageRepository;
use app\repository\AiChatSessionRepository;
use app\model\AiUsageLog;
use app\model\AiChatSession;
use app\model\AiCreditPackage;
use app\exception\BusinessException;
use Exception;

/**
 * AI 服务层（算力计费 + 配额管理 + 统一入口）
 *
 * 职责：
 * 1. 封装 AIClient 调用，自动记录使用日志
 * 2. 算力配额检查与扣减
 * 3. 提供上层业务方法：商品文案、智能客服、数据分析
 *
 * 4 层架构：Controller -> Service -> Repository -> Model
 */
class AiService extends BaseService
{
    private AiClient $aiClient;
    private AiCreditPackageRepository $creditPackageRepository;
    private AiChatSessionRepository $chatSessionRepository;
    private SettingService $settingService;

    public function __construct(
        ?AiUsageLogRepository $repository = null
    ) {
        parent::__construct($repository ?? new AiUsageLogRepository());
        $this->aiClient = new AiClient();
        $this->creditPackageRepository = new AiCreditPackageRepository();
        $this->chatSessionRepository = new AiChatSessionRepository();
        $this->settingService = new SettingService();
    }

    // ============================================================
    // 核心：带计费的 AI 调用
    // ============================================================

    /**
     * 执行 AI 调用（自动计费 + 记录日志）
     *
     * @param int    $appId      应用ID
     * @param int    $adminId    管理员ID（后台调用时）
     * @param int    $userId     用户ID（C端调用时）
     * @param string $feature    功能类型: copywriting|customer_service|analytics
     * @param array  $messages   消息数组
     * @param array  $options    [provider, model, system_prompt, max_tokens, temperature, context]
     * @return AiResponse
     * @throws BusinessException
     */
    public function invoke(
        int $appId,
        int $adminId,
        int $userId,
        string $feature,
        array $messages,
        array $options = []
    ): AiResponse {
        // 1. 检查 AI 功能总开关
        $aiEnabled = $this->settingService->getSetting('ai_enabled', $appId, true);
        if (!$aiEnabled) {
            throw new BusinessException('AI 功能未开启', 403);
        }

        // 2. 检查算力配额
        $billingEnabled = $this->settingService->getSetting('ai_billing_enabled', 0, config('ai.billing.enabled', true));
        $costCalls = config('ai.cost_per_call.' . $feature, 1);

        if ($billingEnabled && $appId > 0) {
            $remaining = $this->creditPackageRepository->getTotalRemainingCalls($appId);
            if ($remaining < $costCalls) {
                throw new BusinessException(
                    "AI 算力不足，当前剩余 {$remaining} 次，本次需要 {$costCalls} 次，请购买算力包",
                    402
                );
            }
        }

        // 3. 调用 AI
        $prompt = $this->extractPrompt($messages);
        $startTime = microtime(true);

        try {
            $response = $this->aiClient->chat($messages, $options);
            $status = AiUsageLog::STATUS_SUCCESS;
            $errorMessage = null;
        } catch (BusinessException $e) {
            $status = AiUsageLog::STATUS_FAILED;
            $errorMessage = $e->getMessage();
            $response = null;
        }

        $durationMs = (int) ((microtime(true) - $startTime) * 1000);

        // 4. 记录使用日志
        $logData = [
            'app_id'        => $appId,
            'admin_id'      => $adminId,
            'user_id'       => $userId,
            'feature'       => $feature,
            'provider'      => $response?->provider ?? ($options['provider'] ?? ''),
            'model'         => $response?->model ?? ($options['model'] ?? ''),
            'prompt'        => mb_substr($prompt, 0, 5000),
            'response'      => $response ? mb_substr($response->content, 0, 5000) : null,
            'input_tokens'  => $response?->inputTokens ?? 0,
            'output_tokens' => $response?->outputTokens ?? 0,
            'cost_calls'    => $status === AiUsageLog::STATUS_SUCCESS ? $costCalls : 0,
            'cost_amount'   => $response?->costUsd ?? 0,
            'duration_ms'   => $durationMs,
            'status'        => $status,
            'error_message' => $errorMessage,
            'context'       => $options['context'] ?? null,
        ];

        $this->repository->create($logData);

        // 5. 扣减算力（仅在成功时）
        if ($status === AiUsageLog::STATUS_SUCCESS && $billingEnabled && $appId > 0) {
            $this->creditPackageRepository->deductCalls($appId, $costCalls);
        }

        // 6. 失败则抛异常
        if ($status === AiUsageLog::STATUS_FAILED) {
            throw new BusinessException($errorMessage, 500);
        }

        return $response;
    }

    /**
     * 从消息数组中提取用户提示词（用于日志记录）
     */
    private function extractPrompt(array $messages): string
    {
        $parts = [];
        foreach ($messages as $msg) {
            if (($msg['role'] ?? '') === 'system') {
                continue;
            }
            $parts[] = $msg['content'] ?? '';
        }
        return implode("\n", $parts);
    }

    // ============================================================
    // 算力配额管理
    // ============================================================

    /**
     * 获取应用算力配额信息
     */
    public function getCreditInfo(int $appId): array
    {
        $totalRemaining = $this->creditPackageRepository->getTotalRemainingCalls($appId);
        $packages = $this->creditPackageRepository->getActivePackages($appId);

        return [
            'remaining_calls'  => $totalRemaining,
            'active_packages'  => $packages->map(function ($pkg) {
                return [
                    'id'              => $pkg->id,
                    'package_name'    => $pkg->package_name,
                    'total_calls'     => $pkg->total_calls,
                    'used_calls'      => $pkg->used_calls,
                    'remaining_calls' => $pkg->remaining_calls,
                    'expire_at'       => $pkg->expire_at,
                    'status'          => $pkg->status,
                    'status_text'     => $pkg->status_text,
                ];
            })->toArray(),
            'packages_config'  => config('ai.billing.packages', []),
        ];
    }

    /**
     * 获取算力包套餐列表（供前端展示购买）
     */
    public function getPackageOptions(): array
    {
        return config('ai.billing.packages', []);
    }

    /**
     * 为应用创建算力包
     */
    public function createCreditPackage(int $appId, int $adminId, array $data): array
    {
        $totalCalls = (int) ($data['total_calls'] ?? 0);
        $price = (float) ($data['price'] ?? 0);
        $packageName = $data['package_name'] ?? '自定义包';

        if ($totalCalls <= 0) {
            throw new BusinessException('算力次数必须大于 0');
        }

        $now = time();
        $package = $this->creditPackageRepository->create([
            'app_id'          => $appId,
            'admin_id'        => $adminId,
            'package_name'    => $packageName,
            'total_calls'     => $totalCalls,
            'used_calls'      => 0,
            'remaining_calls' => $totalCalls,
            'price'           => $price,
            'payment_status'  => AiCreditPackage::PAYMENT_PAID, // 后台直接开通
            'expire_at'       => null,
            'status'          => AiCreditPackage::STATUS_ACTIVE,
            'remark'          => $data['remark'] ?? '后台开通',
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);

        return ['id' => $package->id, 'package_name' => $packageName, 'total_calls' => $totalCalls];
    }

    // ============================================================
    // AI 使用统计
    // ============================================================

    /**
     * 获取 AI 使用统计
     */
    public function getUsageStats(int $appId): array
    {
        return $this->repository->getUsageStats($appId);
    }

    /**
     * 获取使用记录列表
     */
    public function getUsageLogs(int $appId, int $pageSize = 20, array $filters = [])
    {
        return $this->repository->getListByApp($appId, $pageSize, $filters);
    }

    /**
     * 获取使用趋势
     */
    public function getUsageTrend(int $appId, int $days = 30): array
    {
        return $this->repository->getUsageTrend($appId, $days);
    }

    // ============================================================
    // AI 配置管理
    // ============================================================

    /**
     * 获取 AI 配置信息（脱敏）
     */
    public function getAiConfig(): array
    {
        $client = new AiClient();
        $providers = $client->getAvailableProviders();

        return [
            'ai_enabled'          => $this->settingService->getSetting('ai_enabled', 0, true),
            'default_provider'    => $this->settingService->getSetting('ai_default_provider', 0, 'openai'),
            'billing_enabled'     => $this->settingService->getSetting('ai_billing_enabled', 0, true),
            'free_quota'          => $this->settingService->getSetting('ai_free_quota', 0, 50),
            'providers'           => $providers,
            'openai' => [
                'base_url'      => $this->settingService->getSetting('ai_openai_base_url', 0, 'https://api.openai.com/v1'),
                'model'         => $this->settingService->getSetting('ai_openai_model', 0, 'gpt-4o'),
                'api_key_masked'=> $this->maskApiKey($this->settingService->getRawSetting('ai_openai_api_key', 0, '')),
            ],
            'anthropic' => [
                'base_url'      => $this->settingService->getSetting('ai_anthropic_base_url', 0, 'https://api.anthropic.com'),
                'model'         => $this->settingService->getSetting('ai_anthropic_model', 0, 'claude-sonnet-4-20250514'),
                'api_version'   => $this->settingService->getSetting('ai_anthropic_api_version', 0, '2023-06-01'),
                'api_key_masked'=> $this->maskApiKey($this->settingService->getRawSetting('ai_anthropic_api_key', 0, '')),
            ],
        ];
    }

    /**
     * 保存 AI 配置
     */
    public function saveAiConfig(array $data): void
    {
        $allowedKeys = [
            'ai_enabled', 'ai_default_provider', 'ai_billing_enabled', 'ai_free_quota',
            'ai_openai_base_url', 'ai_openai_model',
            'ai_anthropic_base_url', 'ai_anthropic_model', 'ai_anthropic_api_version',
        ];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $data)) {
                $type = match ($key) {
                    'ai_enabled', 'ai_billing_enabled' => 'boolean',
                    'ai_free_quota' => 'integer',
                    default => 'string',
                };
                $this->settingService->setSetting($key, $data[$key], $type, 'ai');
            }
        }

        // API Key 单独处理（加密存储）
        if (!empty($data['ai_openai_api_key'])) {
            $this->settingService->setSetting('ai_openai_api_key', $data['ai_openai_api_key'], 'string', 'ai');
        }
        if (!empty($data['ai_anthropic_api_key'])) {
            $this->settingService->setSetting('ai_anthropic_api_key', $data['ai_anthropic_api_key'], 'string', 'ai');
        }
    }

    // ============================================================
    // AI 会话管理（C端）
    // ============================================================

    /**
     * 获取或创建活跃会话
     */
    public function getOrCreateActiveSession(int $appId, int $userId, string $sessionId)
    {
        return $this->chatSessionRepository->getOrCreateActiveSession($appId, $userId, $sessionId);
    }

    /**
     * 按 session_id 查找会话
     */
    public function findSessionBySessionId(string $sessionId)
    {
        return $this->chatSessionRepository->findBySessionId($sessionId);
    }

    /**
     * API Key 脱敏
     */
    private function maskApiKey(string $key): string
    {
        if (empty($key)) {
            return '';
        }
        $len = strlen($key);
        if ($len <= 8) {
            return str_repeat('*', $len);
        }
        return substr($key, 0, 3) . '****' . substr($key, -4);
    }
}
