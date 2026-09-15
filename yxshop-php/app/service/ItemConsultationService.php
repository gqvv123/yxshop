<?php

namespace app\service;

use app\repository\ItemConsultationRepository;
use app\model\ItemConsultation;
use Exception;

/**
 * 商品咨询的服务类
 *
 * @property ItemConsultationRepository $repository
 */
class ItemConsultationService extends BaseService
{
    public function __construct(?ItemConsultationRepository $repository = null)
    {
        parent::__construct($repository ?? new ItemConsultationRepository());
    }

    public function getByItem(int $itemId, int $appId = 0)
    {
        try {
            return $this->repository->getByItem($itemId, $appId);
        } catch (Exception $e) {
            $this->logError('按商品获取咨询失败', [
                'item_id' => $itemId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByUser(int $userId, int $appId = 0)
    {
        try {
            return $this->repository->getByUser($userId, $appId);
        } catch (Exception $e) {
            $this->logError('按用户获取咨询失败', [
                'user_id' => $userId,
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
            $this->logError('获取待回复咨询失败', [
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
            $this->logError('获取咨询分页列表失败', [
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
            $this->logInfo('回复咨询开始', ['id' => $id, 'replier_id' => $replierId]);
            $consultation = $this->repository->reply($id, $replierId, $replyContent);
            $this->logInfo('回复咨询成功', ['id' => $id]);
            return $consultation;
        } catch (Exception $e) {
            $this->logError('回复咨询失败', [
                'id' => $id,
                'replier_id' => $replierId,
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
            $this->logError('获取咨询统计失败', [
                'app_id' => $conditions['app_id'] ?? 0,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建咨询开始', ['data' => $data]);
            $consultation = $this->repository->create($data);
            $this->logInfo('创建咨询成功', ['id' => $consultation->id]);
            return $consultation;
        } catch (Exception $e) {
            $this->logError('创建咨询失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
