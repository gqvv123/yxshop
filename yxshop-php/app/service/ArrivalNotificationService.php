<?php

namespace app\service;

use app\repository\ArrivalNotificationRepository;
use app\model\ArrivalNotification;
use app\exception\BusinessException;
use Exception;

/**
 * 到货通知服务类
 *
 * @property ArrivalNotificationRepository $repository
 */
class ArrivalNotificationService extends BaseService
{
    public function __construct(?ArrivalNotificationRepository $repository = null)
    {
        parent::__construct($repository ?? new ArrivalNotificationRepository());
    }

    public function getByUser(int $userId, int $appId = 0)
    {
        try {
            return $this->repository->getByUser($userId, $appId);
        } catch (Exception $e) {
            $this->logError('按用户获取通知失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByItem(int $itemId, int $appId = 0)
    {
        try {
            return $this->repository->getByItem($itemId, $appId);
        } catch (Exception $e) {
            $this->logError('按商品获取通知失败', [
                'item_id' => $itemId,
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
            $this->logError('按状态获取通知失败', [
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
            $this->logError('获取待通知失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function existsByUserAndItem(int $userId, int $itemId, int $specId = 0, int $appId = 0): bool
    {
        try {
            return $this->repository->existsByUserAndItem($userId, $itemId, $specId, $appId);
        } catch (Exception $e) {
            $this->logError('检查通知是否存在失败', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'spec_id' => $specId,
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
            $this->logError('获取通知分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function markNotified(int $id)
    {
        try {
            $this->logInfo('标记已通知开始', ['id' => $id]);
            $notification = $this->repository->markNotified($id);
            $this->logInfo('标记已通知成功', ['id' => $id]);
            return $notification;
        } catch (Exception $e) {
            $this->logError('标记已通知失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function subscribe(int $userId, int $itemId, int $specId = 0, int $notifyType = 1, int $appId = 0)
    {
        try {
            $this->logInfo('订阅到货通知开始', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'spec_id' => $specId
            ]);

            if ($this->repository->existsByUserAndItem($userId, $itemId, $specId, $appId)) {
                throw new BusinessException('已订阅过该商品到货通知');
            }

            $notification = $this->repository->create([
                'user_id' => $userId,
                'item_id' => $itemId,
                'spec_id' => $specId,
                'notify_type' => $notifyType,
                'status' => ArrivalNotification::STATUS_PENDING,
                'app_id' => $appId,
            ]);

            $this->logInfo('订阅到货通知成功', ['id' => $notification->id]);
            return $notification;
        } catch (Exception $e) {
            $this->logError('订阅到货通知失败', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建到货通知开始', ['data' => $data]);
            $notification = $this->repository->create($data);
            $this->logInfo('创建到货通知成功', ['id' => $notification->id]);
            return $notification;
        } catch (Exception $e) {
            $this->logError('创建到货通知失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
