<?php

namespace app\admin\controller;

use support\Request;
use app\service\AiService;
use app\service\ItemService;
use app\service\OrderService;
use app\exception\BusinessException;

/**
 * AI 管理控制器
 *
 * 后台 AI 功能入口：
 * - AI 配置管理（服务商、API Key、模型、计费）
 * - AI 算力包管理
 * - AI 使用记录与统计
 * - AI 一键生成商品文案
 * - AI 数据分析报表
 */
class AiController extends BaseController
{
    protected AiService $aiService;
    protected ItemService $itemService;
    protected OrderService $orderService;

    public function __construct()
    {
        parent::__construct();
        $this->aiService = new AiService();
        $this->itemService = new ItemService();
        $this->orderService = new OrderService();
    }

    // ============================================================
    // AI 配置管理
    // ============================================================

    /**
     * 获取 AI 配置
     * GET /admin/api/ai/config
     */
    public function getConfig(Request $request)
    {
        return $this->success($this->aiService->getAiConfig());
    }

    /**
     * 保存 AI 配置
     * POST /admin/api/ai/config
     */
    public function saveConfig(Request $request)
    {
        $data = $request->post();
        $this->aiService->saveAiConfig($data);
        return $this->success([], 'AI 配置保存成功');
    }

    // ============================================================
    // 算力包管理
    // ============================================================

    /**
     * 获取算力包信息
     * GET /admin/api/ai/credits
     */
    public function credits(Request $request)
    {
        $appId = $this->getAppId($request);
        return $this->success($this->aiService->getCreditInfo($appId));
    }

    /**
     * 获取算力包套餐列表
     * GET /admin/api/ai/packages
     */
    public function packages(Request $request)
    {
        return $this->success($this->aiService->getPackageOptions());
    }

    /**
     * 为商家开通算力包（后台直接开通）
     * POST /admin/api/ai/credits/create
     */
    public function createCredits(Request $request)
    {
        $appId = $this->getAppId($request);
        $adminId = $this->admin['id'] ?? 0;
        $data = $request->post();

        $result = $this->aiService->createCreditPackage($appId, $adminId, $data);
        return $this->success($result, '算力包开通成功');
    }

    // ============================================================
    // AI 使用记录与统计
    // ============================================================

    /**
     * AI 使用统计
     * GET /admin/api/ai/stats
     */
    public function stats(Request $request)
    {
        $appId = $this->getAppId($request);
        $stats = $this->aiService->getUsageStats($appId);
        $trend = $this->aiService->getUsageTrend($appId, 30);
        $credits = $this->aiService->getCreditInfo($appId);

        return $this->success([
            'usage'   => $stats,
            'trend'   => $trend,
            'credits' => $credits,
        ]);
    }

    /**
     * AI 使用记录列表
     * GET /admin/api/ai/logs
     */
    public function logs(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->get('page_size', 20)));

        $filters = [
            'feature'    => $request->get('feature', ''),
            'provider'   => $request->get('provider', ''),
            'status'     => $request->get('status', ''),
            'start_date' => $request->get('start_date', ''),
            'end_date'   => $request->get('end_date', ''),
        ];

        $result = $this->aiService->getUsageLogs($appId, $pageSize, $filters);
        return $this->success($result);
    }

    // ============================================================
    // AI 一键生成商品文案
    // ============================================================

    /**
     * AI 生成商品文案
     * POST /admin/api/ai/copywriting
     *
     * 请求参数：
     * - item_id: 商品ID（可选，有则自动获取商品信息）
     * - name: 商品名称（无 item_id 时手动传入）
     * - category: 商品分类
     * - features: 商品卖点（数组）
     * - price: 商品价格
     * - style: 文案风格 (professional|casual|luxury|cute)
     * - type: 生成类型 (title|description|short|full)
     */
    public function copywriting(Request $request)
    {
        try {
            $appId = $this->getAppId($request);
            $adminId = $this->admin['id'] ?? 0;
            $itemId = $request->post('item_id');
            $style = $request->post('style', 'professional');
            $type = $request->post('type', 'full');

            // 获取商品信息
            $itemData = null;
            if ($itemId) {
                $itemData = $this->itemService->findWithRelations($itemId);
            }

            $name = $request->post('name', $itemData['name'] ?? '');
            $category = $request->post('category', $itemData['category_name'] ?? '');
            $features = $request->post('features', []);
            $price = $request->post('price', $itemData['price'] ?? 0);

            if (empty($name)) {
                return $this->error('商品名称不能为空');
            }

            // 构建系统提示词
            $styleMap = [
                'professional' => '专业严谨',
                'casual'        => '轻松活泼',
                'luxury'        => '高端奢华',
                'cute'          => '可爱俏皮',
            ];
            $styleText = $styleMap[$style] ?? '专业严谨';

            $typeMap = [
                'title'       => '商品标题（20字以内）',
                'description' => '商品描述（100-200字）',
                'short'       => '简短卖点文案（50字以内）',
                'full'        => '完整商品详情文案（含标题、卖点、描述、推荐语）',
            ];
            $typeText = $typeMap[$type] ?? $typeMap['full'];

            $featuresText = is_array($features) ? implode('、', $features) : ($features ?: '未提供');
            $priceText = $price ? "¥{$price}" : '未定价';

            $systemPrompt = "你是一位专业的电商文案策划专家，擅长为商品撰写有吸引力的营销文案。\n"
                . "文案风格要求：{$styleText}\n"
                . "生成内容类型：{$typeText}\n"
                . "请根据商品信息生成高质量的商品文案，突出产品卖点，吸引买家购买。\n"
                . "使用 Markdown 格式输出。";

            $userPrompt = "请为以下商品生成文案：\n"
                . "商品名称：{$name}\n"
                . "商品分类：{$category}\n"
                . "商品卖点：{$featuresText}\n"
                . "商品价格：{$priceText}\n"
                . "请生成{$typeText}。";

            $response = $this->aiService->invoke(
                $appId,
                $adminId,
                0,
                'copywriting',
                [['role' => 'user', 'content' => $userPrompt]],
                [
                    'system_prompt' => $systemPrompt,
                    'context'       => ['item_id' => $itemId, 'type' => $type, 'style' => $style],
                ]
            );

            return $this->success([
                'content'       => $response->content,
                'model'         => $response->model,
                'provider'      => $response->provider,
                'tokens'        => $response->totalTokens(),
            ]);

        } catch (BusinessException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error('AI 生成失败: ' . $e->getMessage());
        }
    }

    // ============================================================
    // AI 数据分析报表
    // ============================================================

    /**
     * AI 数据分析
     * POST /admin/api/ai/analytics
     *
     * 请求参数：
     * - question: 自然语言提问（可选）
     * - period: 分析周期 (7d|30d|90d)
     */
    public function analytics(Request $request)
    {
        try {
            $appId = $this->getAppId($request);
            $adminId = $this->admin['id'] ?? 0;
            $question = $request->post('question', '请分析近期销售情况');
            $period = $request->post('period', '30d');

            // 收集业务数据
            $days = match ($period) {
                '7d'   => 7,
                '90d'  => 90,
                default => 30,
            };

            $salesReport = $this->orderService->getSalesReport($appId);
            $salesReport = array_slice($salesReport, -$days);

            $totalSales = array_sum(array_column($salesReport, 'sales_amount'));
            $totalOrders = array_sum(array_column($salesReport, 'order_count'));
            $avgOrderValue = $totalOrders > 0 ? round($totalSales / $totalOrders, 2) : 0;

            $orderStats = $this->orderService->getStatistics($appId);
            $creditInfo = $this->aiService->getCreditInfo($appId);

            // 构建分析数据摘要
            $dataSummary = "=== 近{$days}天销售数据 ===\n"
                . "总销售额：¥" . number_format($totalSales, 2) . "\n"
                . "总订单数：{$totalOrders}\n"
                . "客单价：¥{$avgOrderValue}\n"
                . "今日订单：{$orderStats['todayOrders']}\n"
                . "今日营收：¥" . number_format($orderStats['todayRevenue'], 2) . "\n"
                . "待发货：{$orderStats['pendingShip']}\n"
                . "待退款：{$orderStats['pendingRefund']}\n"
                . "日销售明细：\n";

            foreach ($salesReport as $day) {
                $dataSummary .= "  {$day['date']}: 订单{$day['order_count']}笔, 销售额¥{$day['sales_amount']}\n";
            }

            $systemPrompt = "你是一位专业的电商数据分析师，擅长从销售数据中发现趋势和洞察。\n"
                . "请基于提供的数据，给出深入的分析报告，包括：\n"
                . "1. 销售趋势分析（上升/下降/波动原因）\n"
                . "2. 热销商品特征推断\n"
                . "3. 运营建议（如何提升销量、优化库存）\n"
                . "4. 潜在风险提示\n"
                . "使用 Markdown 格式输出，内容要具体、可操作。";

            $userPrompt = "用户问题：{$question}\n\n" . $dataSummary;

            $response = $this->aiService->invoke(
                $appId,
                $adminId,
                0,
                'analytics',
                [['role' => 'user', 'content' => $userPrompt]],
                [
                    'system_prompt' => $systemPrompt,
                    'context'       => ['period' => $period, 'question' => $question],
                ]
            );

            return $this->success([
                'content'       => $response->content,
                'model'         => $response->model,
                'provider'      => $response->provider,
                'tokens'        => $response->totalTokens(),
                'data_summary'  => [
                    'days'           => $days,
                    'total_sales'    => $totalSales,
                    'total_orders'   => $totalOrders,
                    'avg_order_value'=> $avgOrderValue,
                ],
            ]);

        } catch (BusinessException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error('AI 分析失败: ' . $e->getMessage());
        }
    }
}
