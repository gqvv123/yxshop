<?php

namespace app\service;

use app\repository\UserFeedbackRepository;
use app\model\UserFeedback;
use Exception;

/**
 * 用户反馈服务类
 *
 * @property UserFeedbackRepository $repository
 */
class UserFeedbackService extends BaseService
{
    public function __construct(?UserFeedbackRepository $repository = null)
    {
        parent::__construct($repository ?? new UserFeedbackRepository());
    }

    public function getByUser(int $userId, int $appId = 0)
    {
        try {
            return $this->repository->getByUser($userId, $appId);
        } catch (Exception $e) {
            $this->logError('按用户获取反馈失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByType(int $feedbackType, int $appId = 0)
    {
        try {
            return $this->repository->getByType($feedbackType, $appId);
        } catch (Exception $e) {
            $this->logError('按类型获取反馈失败', [
                'feedback_type' => $feedbackType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByStatus(int $status, int $appId = 0)
    {
        try {
            return $this->repository->getByStatus($status, $appId);
        } catch (Exception $e) {
            $this->logError('按状态获取反馈失败', [
                'status' => $status,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPending(int $appId = 0)
    {
        try {
            return $this->repository->getPending($appId);
        } catch (Exception $e) {
            $this->logError('获取待回复反馈失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        try {
            return $this->repository->getPaginatedList($appId, $filters, $pageSize);
        } catch (Exception $e) {
            $this->logError('获取反馈分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function reply(int $id, int $replierId, string $replyContent)
    {
        try {
            $this->logInfo('回复反馈开始', ['id' => $id, 'replier_id' => $replierId]);
            $feedback = $this->repository->reply($id, $replierId, $replyContent);
            $this->logInfo('回复反馈成功', ['id' => $id]);
            return $feedback;
        } catch (Exception $e) {
            $this->logError('回复反馈失败', [
                'id' => $id,
                'replier_id' => $replierId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function close(int $id)
    {
        try {
            $this->logInfo('关闭反馈开始', ['id' => $id]);
            $feedback = $this->repository->close($id);
            $this->logInfo('关闭反馈成功', ['id' => $id]);
            return $feedback;
        } catch (Exception $e) {
            $this->logError('关闭反馈失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getStats(array $conditions = [])
    {
        try {
            return $this->repository->getStats($conditions);
        } catch (Exception $e) {
            $this->logError('获取反馈统计失败', [
                'app_id' => $conditions['app_id'] ?? 0,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建反馈开始', ['data' => $data]);
            $feedback = $this->repository->create($data);
            $this->logInfo('创建反馈成功', ['id' => $feedback->id]);
            return $feedback;
        } catch (Exception $e) {
            $this->logError('创建反馈失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户反馈列表（分页）
     */
    public function getMyListPaginated(int $userId, int $page = 1, int $pageSize = 20)
    {
        return $this->repository->query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }
}
