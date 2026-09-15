<?php

namespace app\repository;

use app\model\ArrivalNotification;

/**
 * 到货通知仓储
 */
class ArrivalNotificationRepository extends BaseRepository
{
    protected $model = ArrivalNotification::class;

    /**
     * 按用户获取通知
     */
    public function getByUser(int $userId, int $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['item'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * 按商品获取通知
     */
    public function getByItem(int $itemId, int $appId = 0)
    {
        $query = $this->query()->where('item_id', $itemId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['user'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * 按状态获取通知
     */
    public function getByStatus(int $status, int $appId = 0)
    {
        $query = $this->query()->where('status', $status);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['user', 'item'])->orderBy('created_at', 'desc')->get();
    }

    /**
     * 获取待通知
     */
    public function getPending(int $appId = 0)
    {
        return $this->getByStatus(ArrivalNotification::STATUS_PENDING, $appId);
    }

    /**
     * 检查是否已存在通知
     */
    public function existsByUserAndItem(int $userId, int $itemId, int $specId = 0, int $appId = 0): bool
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->where('spec_id', $specId)
            ->where('status', ArrivalNotification::STATUS_PENDING);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->exists();
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
        if (!empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        return $query->with(['user', 'item'])->paginate($pageSize);
    }

    /**
     * 标记已通知
     */
    public function markNotified(int $id): ArrivalNotification
    {
        $notification = $this->findOrFail($id);
        $notification->status = ArrivalNotification::STATUS_NOTIFIED;
        $notification->notified_at = time();
        $notification->save();
        return $notification;
    }
}