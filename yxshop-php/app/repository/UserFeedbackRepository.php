<?php

namespace app\repository;

use app\model\UserFeedback;

/**
 * 用户反馈仓储
 */
class UserFeedbackRepository extends BaseRepository
{
    protected $model = UserFeedback::class;

    /**
     * 按用户获取反馈
     */
    public function getByUser(int $userId, int $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 按类型获取反馈
     */
    public function getByType(int $feedbackType, int $appId = 0)
    {
        $query = $this->query()->where('feedback_type', $feedbackType);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['user'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * 按状态获取反馈
     */
    public function getByStatus(int $status, int $appId = 0)
    {
        $query = $this->query()->where('status', $status);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['user'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * 获取待回复反馈
     */
    public function getPending(int $appId = 0)
    {
        return $this->getByStatus(UserFeedback::STATUS_PENDING, $appId);
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if (isset($filters['feedback_type']) && $filters['feedback_type'] !== '') {
            $query->where('feedback_type', $filters['feedback_type']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        return $query->with(['user', 'replier'])->paginate($pageSize);
    }

    /**
     * 回复反馈
     */
    public function reply(int $id, int $replierId, string $replyContent): UserFeedback
    {
        $feedback = $this->findOrFail($id);
        $feedback->reply_content = $replyContent;
        $feedback->replier_id = $replierId;
        $feedback->status = UserFeedback::STATUS_REPLIED;
        $feedback->save();
        return $feedback;
    }

    /**
     * 关闭反馈
     */
    public function close(int $id): UserFeedback
    {
        $feedback = $this->findOrFail($id);
        $feedback->status = UserFeedback::STATUS_CLOSED;
        $feedback->save();
        return $feedback;
    }

    /**
     * 获取反馈统计
     */
    public function getStats(array $conditions = [])
    {
        $appId = $conditions['app_id'] ?? 0;
        $base = $this->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        return [
            'total' => (clone $base)->count(),
            'pending' => (clone $base)->where('status', UserFeedback::STATUS_PENDING)->count(),
            'replied' => (clone $base)->where('status', UserFeedback::STATUS_REPLIED)->count(),
            'closed' => (clone $base)->where('status', UserFeedback::STATUS_CLOSED)->count(),
        ];
    }
}