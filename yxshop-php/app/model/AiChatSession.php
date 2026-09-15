<?php

namespace app\model;

/**
 * AI 智能客服会话模型
 *
 * 存储买家与 AI 客服的对话历史
 */
class AiChatSession extends BaseModel
{
    protected $table = 'yxshop_ai_chat_sessions';

    protected $fillable = [
        'app_id', 'user_id', 'session_id',
        'messages', 'context', 'status',
        'last_message_at',
        'created_at', 'updated_at',
    ];

    protected $casts = [
        'app_id'          => 'integer',
        'user_id'         => 'integer',
        'messages'        => 'array',
        'context'         => 'array',
        'status'          => 'integer',
        'last_message_at' => 'integer',
        'created_at'      => 'integer',
        'updated_at'      => 'integer',
    ];

    // 状态常量
    const STATUS_CLOSED  = 0;
    const STATUS_ACTIVE  = 1;

    // 对话角色常量
    const ROLE_USER      = 'user';
    const ROLE_ASSISTANT = 'assistant';

    /**
     * 关联用户
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 追加一条消息到对话历史
     */
    public function appendMessage(string $role, string $content): void
    {
        $messages = $this->messages ?: [];
        $messages[] = [
            'role'    => $role,
            'content' => $content,
            'time'    => time(),
        ];
        $this->messages = $messages;
        $this->last_message_at = time();
    }

    /**
     * 获取最近 N 条对话（用于构建 prompt 上下文）
     */
    public function getRecentMessages(int $limit = 10): array
    {
        $messages = $this->messages ?: [];
        return array_slice($messages, -$limit);
    }
}
