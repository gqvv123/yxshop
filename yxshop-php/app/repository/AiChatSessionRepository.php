<?php

namespace app\repository;

use app\model\AiChatSession;

/**
 * AI 智能客服会话仓储
 */
class AiChatSessionRepository extends BaseRepository
{
    public function __construct(?AiChatSession $model = null)
    {
        parent::__construct($model ?? new AiChatSession());
    }

    /**
     * 通过 session_id 查找会话
     */
    public function findBySessionId(string $sessionId): ?AiChatSession
    {
        return $this->query()
            ->where('session_id', $sessionId)
            ->first();
    }

    /**
     * 获取或创建用户活跃会话
     */
    public function getOrCreateActiveSession(int $appId, int $userId, string $sessionId): AiChatSession
    {
        $session = $this->findBySessionId($sessionId);
        if ($session && $session->status === AiChatSession::STATUS_ACTIVE) {
            return $session;
        }

        // 关闭旧会话
        if ($session) {
            $session->status = AiChatSession::STATUS_CLOSED;
            $session->save();
        }

        // 创建新会话
        $now = time();
        return $this->create([
            'app_id'          => $appId,
            'user_id'         => $userId,
            'session_id'      => $sessionId,
            'messages'        => [],
            'context'         => null,
            'status'          => AiChatSession::STATUS_ACTIVE,
            'last_message_at' => $now,
            'created_at'      => $now,
            'updated_at'      => $now,
        ]);
    }
}
