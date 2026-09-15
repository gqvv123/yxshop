<?php

namespace app\service;

use app\repository\CommentRepository;
use app\model\Comment;
use app\exception\BusinessException;
use app\validate\CommentValidate;
use Exception;

/**
 * 评价服务类
 *
 * @property CommentRepository $repository
 */
class CommentService extends BaseService
{
    public function __construct(?CommentRepository $repository = null)
    {
        parent::__construct($repository ?? new CommentRepository());
    }

    /**
     * 获取商品评价列表
     */
    public function getItemComments($itemId, $status = null, $appId = 0)
    {
        try {
            $this->logInfo('获取商品评价列表开始', [
                'item_id' => $itemId,
                'status' => $status,
                'app_id' => $appId
            ]);

            $comments = $this->repository->getItemComments($itemId, $status, $appId);

            $this->logInfo('获取商品评价列表成功', ['item_id' => $itemId]);
            return $comments;

        } catch (Exception $e) {
            $this->logError('获取商品评价列表失败', [
                'item_id' => $itemId,
                'status' => $status,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建评价
     */
    public function createComment(array $data)
    {
        try {
            $this->logInfo('创建评价开始', ['data' => $data]);

            $this->validateWith(CommentValidate::class, 'create', $data);

            // 检查是否已评价
            $existing = $this->repository->findByUserAndOrder(
                (int) $data['user_id'],
                (int) $data['order_id']
            );

            if ($existing) {
                throw new BusinessException('该订单已评价');
            }

            $comment = $this->repository->create($data);

            // 更新商品评价数量
            $this->updateItemCommentCount($data['item_id']);

            $this->logInfo('创建评价成功', ['comment_id' => $comment->id]);
            return $comment;

        } catch (Exception $e) {
            $this->logError('创建评价失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 审核评价
     */
    public function auditComment($id, $status, $reply = '')
    {
        try {
            $this->logInfo('审核评价开始', ['id' => $id, 'status' => $status]);

            $comment = $this->repository->findOrFail($id);

            if ($status == Comment::STATUS_APPROVED) {
                $comment->approve();
            } else {
                $comment->reject($reply);
            }

            $this->logInfo('审核评价成功', ['comment_id' => $id]);
            return $comment;

        } catch (Exception $e) {
            $this->logError('审核评价失败', [
                'id' => $id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取待审核评价
     */
    public function getPendingComments($appId = 0)
    {
        try {
            return $this->repository->getPendingComments($appId);

        } catch (Exception $e) {
            $this->logError('获取待审核评价失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户评价列表
     */
    public function getUserComments($userId, $appId = 0)
    {
        try {
            return $this->repository->getUserComments($userId, $appId);

        } catch (Exception $e) {
            $this->logError('获取用户评价列表失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取商品评价统计
     */
    public function getItemCommentStats($itemId, $appId = 0)
    {
        try {
            return $this->repository->getItemCommentStats($itemId, $appId);

        } catch (Exception $e) {
            $this->logError('获取商品评价统计失败', [
                'item_id' => $itemId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 搜索评价
     */
    public function searchComments($keyword, $appId = 0)
    {
        try {
            return $this->repository->searchComments($keyword, $appId);

        } catch (Exception $e) {
            $this->logError('搜索评价失败', [
                'keyword' => $keyword,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新商品评价数量
     */
    private function updateItemCommentCount($itemId)
    {
        try {
            $count = $this->repository->countApprovedByItem((int) $itemId);

            $this->logInfo('更新商品评价数量', ['item_id' => $itemId, 'count' => $count]);

        } catch (Exception $e) {
            $this->logError('更新商品评价数量失败', [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function getCommentList($page, $limit, array $filters = [])
    {
        return $this->repository->getCommentList($page, $limit, $filters);
    }

    public function getCommentById($id)
    {
        return $this->repository->findOrFail($id);
    }

    public function replyComment($id, array $data)
    {
        return $this->repository->replyComment($id, $data);
    }

    public function updateCommentStatus($id, $status)
    {
        $comment = $this->repository->findOrFail($id);
        $comment->status = $status;
        $comment->save();
        return $comment;
    }

    public function deleteComment($id)
    {
        return $this->repository->delete($id);
    }

    public function getCommentStats($appId = 0)
    {
        return $this->repository->getCommentStats($appId);
    }

    public function exportComments($startDate, $endDate, $rating = '')
    {
        return $this->repository->exportComments($startDate, $endDate, $rating);
    }

    // ============================================================
    // C 端接口增强
    // ============================================================

    /**
     * 获取商品评价摘要（评分分布+好评率+总评数）
     *
     * @param int $itemId
     * @return array
     */
    public function getItemReviewSummary(int $itemId): array
    {
        try {
            $base = $this->repository->query()
                ->where('item_id', $itemId)
                ->where('status', Comment::STATUS_APPROVED);

            $total = (clone $base)->count();
            if ($total === 0) {
                return [
                    'total'        => 0,
                    'avg_score'    => 0,
                    'good_rate'    => 0,
                    'good_count'   => 0,
                    'medium_count' => 0,
                    'bad_count'    => 0,
                    'with_image_count' => 0,
                ];
            }

            $goodCount   = (clone $base)->where('score', Comment::SCORE_GOOD)->count();
            $mediumCount = (clone $base)->where('score', Comment::SCORE_MEDIUM)->count();
            $badCount    = (clone $base)->where('score', Comment::SCORE_BAD)->count();
            $withImage   = (clone $base)->whereNotNull('images')->where('images', '!=', '[]')->where('images', '!=', '')->count();
            $avgScore    = (clone $base)->avg('score');

            // score 10=好评, 20=中评, 30=差评；前端展示 5 分制
            $avgRating = round($avgScore / 10, 1);

            return [
                'total'            => $total,
                'avg_score'        => $avgRating,
                'good_rate'        => round($goodCount / $total * 100, 1),
                'good_count'       => $goodCount,
                'medium_count'     => $mediumCount,
                'bad_count'        => $badCount,
                'with_image_count' => $withImage,
            ];
        } catch (Exception $e) {
            $this->logError('获取商品评价摘要失败', ['item_id' => $itemId, 'error' => $e->getMessage()]);
            return [
                'total'        => 0,
                'avg_score'    => 0,
                'good_rate'    => 0,
                'good_count'   => 0,
                'medium_count' => 0,
                'bad_count'    => 0,
                'with_image_count' => 0,
            ];
        }
    }

    /**
     * 获取商品评价分页列表（C 端）
     *
     * @param int $itemId
     * @param string $filter  all|good|medium|bad|with_image
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    public function getItemCommentsPaginated(int $itemId, string $filter = 'all', int $page = 1, int $pageSize = 10): array
    {
        try {
            $base = $this->repository->query()
                ->where('item_id', $itemId)
                ->where('status', Comment::STATUS_APPROVED)
                ->with(['user']);

            switch ($filter) {
                case 'good':
                    $base->where('score', Comment::SCORE_GOOD);
                    break;
                case 'medium':
                    $base->where('score', Comment::SCORE_MEDIUM);
                    break;
                case 'bad':
                    $base->where('score', Comment::SCORE_BAD);
                    break;
                case 'with_image':
                    $base->whereNotNull('images')->where('images', '!=', '[]')->where('images', '!=', '');
                    break;
            }

            $total = (clone $base)->count();
            $offset = ($page - 1) * $pageSize;
            $list = $base->orderBy('is_recommend', 'desc')
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($pageSize)
                ->get()
                ->map(function ($c) {
                    $images = $c->images;
                    if (is_string($images)) {
                        $decoded = json_decode($images, true);
                        $images = is_array($decoded) ? $decoded : [];
                    }
                    return [
                        'id'            => $c->id,
                        'score'         => $c->score,
                        'score_text'    => $c->score == Comment::SCORE_GOOD ? '好评'
                                         : ($c->score == Comment::SCORE_MEDIUM ? '中评' : '差评'),
                        'content'       => $c->content,
                        'images'        => $images ?? [],
                        'is_anonymous'  => (int) $c->is_anonymous,
                        'is_recommend'  => (int) $c->is_recommend,
                        'reply_content' => $c->reply_content ?? '',
                        'reply_time'    => $c->reply_time ?? 0,
                        'user_nickname' => $c->is_anonymous ? '匿名用户' : ($c->user->nickname ?? '用户' . $c->user_id),
                        'user_avatar'   => $c->is_anonymous ? '' : ($c->user->avatar_url ?? ''),
                        'created_at'    => $c->created_at,
                    ];
                });

            return [
                'list'       => $list,
                'total'      => $total,
                'page'       => $page,
                'page_size'  => $pageSize,
            ];
        } catch (Exception $e) {
            $this->logError('获取商品评价分页列表失败', [
                'item_id' => $itemId,
                'filter'  => $filter,
                'error'   => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}
