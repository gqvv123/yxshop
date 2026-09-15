<?php

namespace app\repository;

use app\model\Comment;

/**
 * 评价仓储类
 */
class CommentRepository extends BaseRepository
{
    protected $model = Comment::class;

    /**
     * 获取商品评价列表
     */
    public function getItemComments($itemId, $status = null, $appId = 0)
    {
        $query = $this->query()
            ->where('item_id', $itemId)
            ->with(['user'])
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取用户评价列表
     */
    public function getUserComments($userId, $appId = 0)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->with(['item.images'])
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取待审核评价
     */
    public function getPendingComments($appId = 0)
    {
        $query = $this->query()
            ->where('status', Comment::STATUS_PENDING)
            ->with(['user', 'item'])
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取商品评价统计
     */
    public function getItemCommentStats($itemId, $appId = 0)
    {
        $base = $this->query()->where('item_id', $itemId);

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total' => (clone $base)->count(),
            'approved' => (clone $base)->where('status', Comment::STATUS_APPROVED)->count(),
            'pending' => (clone $base)->where('status', Comment::STATUS_PENDING)->count(),
            'rejected' => (clone $base)->where('status', Comment::STATUS_REJECTED)->count(),
            'avg_score' => (clone $base)->where('status', Comment::STATUS_APPROVED)->avg('score'),
        ];
    }

    /**
     * 获取评价统计
     */
    public function getCommentStats($appId = 0)
    {
        $base = $this->query();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total' => (clone $base)->count(),
            'approved' => (clone $base)->where('status', Comment::STATUS_APPROVED)->count(),
            'pending' => (clone $base)->where('status', Comment::STATUS_PENDING)->count(),
            'rejected' => (clone $base)->where('status', Comment::STATUS_REJECTED)->count(),
        ];
    }

    /**
     * 搜索评价
     */
    public function searchComments($keyword, $appId = 0)
    {
        $query = $this->query()
            ->where('content', 'like', '%' . $keyword . '%')
            ->with(['user', 'item'])
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    public function getCommentList($page, $pageSize, array $filters = [])
    {
        $query = $this->query()
            ->with(['user', 'item'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['rating'])) {
            $query->where('score', $filters['rating']);
        }

        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['app_id'])) {
            $query->where('app_id', $filters['app_id']);
        }

        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    public function replyComment($id, array $data)
    {
        $comment = $this->findOrFail($id);
        $comment->reply_content = $data['reply_content'] ?? '';
        $comment->reply_time = time();
        $comment->save();
        return $comment;
    }

    public function exportComments($startDate, $endDate, $rating = '')
    {
        $query = $this->query()
            ->with(['user', 'item'])
            ->orderBy('created_at', 'desc');

        if (!empty($startDate)) {
            $query->where('created_at', '>=', strtotime($startDate));
        }

        if (!empty($endDate)) {
            $query->where('created_at', '<=', strtotime($endDate . ' 23:59:59'));
        }

        if (!empty($rating)) {
            $query->where('score', $rating);
        }

        return $query->get();
    }

    /**
     * 按 (user_id, order_id) 查单条评价（用于查重）
     */
    public function findByUserAndOrder(int $userId, int $orderId)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('order_id', $orderId)
            ->first();
    }

    /**
     * 商品已审核通过的评价数（用于回写 items.comment_count）
     */
    public function countApprovedByItem(int $itemId): int
    {
        return $this->query()
            ->where('item_id', $itemId)
            ->where('status', \app\model\Comment::STATUS_APPROVED)
            ->count();
    }

}
