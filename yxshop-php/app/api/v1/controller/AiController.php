<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\AiService;
use app\service\ItemService;
use app\exception\BusinessException;

/**
 * AI 智能客服（C 端）
 *
 * 4 层架构：Controller -> Service -> Repository -> Model
 * 控制器禁止直接 use app\model\*
 */
class AiController extends BaseController
{
    private AiService $aiService;
    private ItemService $itemService;

    public function __construct()
    {
        $this->aiService = new AiService();
        $this->itemService = new ItemService();
    }

    /**
     * AI 智能客服 - 发送消息
     *
     * POST /api/v1/ai/chat
     *
     * 请求参数：
     * - session_id: 会话ID（首次可不传，由服务端生成）
     * - message: 用户消息内容
     * - item_id: 当前浏览的商品ID（可选，作为上下文）
     */
    public function chat(Request $request)
    {
        try {
            $userId = $this->getCurrentUserId($request);
            if (!$userId) {
                return $this->error('用户未登录');
            }

            $message = trim($request->post('message', ''));
            if (empty($message)) {
                return $this->error('消息不能为空');
            }

            $sessionId = $request->post('session_id', '');
            $itemId = $request->post('item_id');
            $appId = (int) $request->get('app_id', 0);

            // 获取用户信息以补充 appId
            if (!$appId) {
                $userService = new \app\service\UserService();
                $user = $userService->find($userId);
                $appId = (int) ($user->app_id ?? 0);
            }

            // 生成 session_id（如果未提供）
            if (empty($sessionId)) {
                $sessionId = md5($userId . '_' . time() . '_' . uniqid());
            }

            // 获取或创建会话
            $session = $this->aiService->getOrCreateActiveSession($appId, $userId, $sessionId);

            // 记录用户消息
            $session->appendMessage('user', $message);
            $session->save();

            // 如果有 item_id，获取商品信息作为上下文
            $itemContext = '';
            if ($itemId) {
                try {
                    $item = $this->itemService->findWithRelations($itemId);
                    $itemContext = "\n\n当前用户正在浏览的商品信息：\n"
                        . "商品名称：{$item['name']}\n"
                        . "商品价格：¥{$item['price']}\n"
                        . "商品描述：{$item['description']}\n"
                        . "商品分类：{$item['category_name']}\n";
                } catch (\Exception $e) {
                    // 忽略商品信息获取失败
                }
            }

            // 构建系统提示词
            $systemPrompt = "你是一个友好专业的商城智能客服助手。\n"
                . "你的职责是：\n"
                . "1. 回答买家关于商品的问题（尺寸、材质、使用方法等）\n"
                . "2. 提供购物建议和推荐\n"
                . "3. 解答订单、支付、物流相关疑问\n"
                . "4. 处理售后问题咨询\n\n"
                . "回复要求：\n"
                . "- 回复简洁明了，避免过长\n"
                . "- 语气友好、礼貌\n"
                . "- 如不确定具体信息，引导用户联系人工客服\n"
                . "- 不要编造不存在的商品信息"
                . $itemContext;

            // 获取最近对话历史（取最近 10 条作为上下文）
            $recentMessages = $session->getRecentMessages(10);
            $messages = [];
            foreach ($recentMessages as $msg) {
                $messages[] = [
                    'role'    => $msg['role'],
                    'content' => $msg['content'],
                ];
            }

            // 调用 AI
            $response = $this->aiService->invoke(
                $appId,
                0,
                $userId,
                'customer_service',
                $messages,
                [
                    'system_prompt' => $systemPrompt,
                    'max_tokens'    => 1024,
                    'temperature'   => 0.5,
                    'context'       => ['session_id' => $sessionId, 'item_id' => $itemId],
                ]
            );

            // 记录 AI 回复到会话
            $session->appendMessage('assistant', $response->content);
            $session->save();

            return $this->success([
                'session_id' => $sessionId,
                'reply'      => $response->content,
                'model'      => $response->model,
            ]);

        } catch (BusinessException $e) {
            return $this->error($e->getMessage(), $e->getCode());
        } catch (\Exception $e) {
            return $this->error('AI 客服暂时不可用: ' . $e->getMessage());
        }
    }

    /**
     * AI 智能客服 - 获取会话历史
     *
     * GET /api/v1/ai/chat-history?session_id=xxx
     */
    public function chatHistory(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $sessionId = $request->get('session_id', '');
        if (empty($sessionId)) {
            return $this->success(['messages' => []]);
        }

        $session = $this->aiService->findSessionBySessionId($sessionId);

        if (!$session || $session->user_id != $userId) {
            return $this->success(['messages' => []]);
        }

        return $this->success([
            'session_id' => $sessionId,
            'messages'   => $session->messages ?: [],
        ]);
    }

    /**
     * 获取用户 AI 算力余额（C 端展示）
     *
     * GET /api/v1/ai/credits
     */
    public function credits(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $userService = new \app\service\UserService();
        $user = $userService->find($userId);
        $appId = (int) ($user->app_id ?? 0);

        $creditInfo = $this->aiService->getCreditInfo($appId);

        return $this->success([
            'remaining_calls' => $creditInfo['remaining_calls'],
        ]);
    }
}
