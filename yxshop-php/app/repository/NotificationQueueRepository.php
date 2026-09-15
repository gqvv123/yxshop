<?php

namespace app\repository;

use app\model\NotificationQueue;

/**
 * 通知队列仓储类
 */
class NotificationQueueRepository extends BaseRepository
{
    protected $model = NotificationQueue::class;

    /**
     * 获取队列列表
     */
    public function getQueues($sendType = null, $sendStatus = null, $sendPriority = null, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->with(['scene', 'template', 'user'])
            ->orderBy('send_priority', 'desc')
            ->orderBy('created_at', 'asc');

        if ($sendType !== null) {
            $query->where('send_type', $sendType);
        }

        if ($sendStatus !== null) {
            $query->where('send_status', $sendStatus);
        }

        if ($sendPriority !== null) {
            $query->where('send_priority', $sendPriority);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 获取待发送队列
     */
    public function getPendingQueues($sendType = null, $appId = 0, $limit = 100)
    {
        $query = $this->query()
            ->where('send_status', NotificationQueue::STATUS_PENDING)
            ->where('send_attempts', '<', 'send_max_attempts')
            ->where(function($q) {
                $q->whereNull('send_next_time')
                  ->orWhere('send_next_time', '<=', time());
            })
            ->with(['scene', 'template', 'user'])
            ->orderBy('send_priority', 'desc')
            ->orderBy('created_at', 'asc');

        if ($sendType !== null) {
            $query->where('send_type', $sendType);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 获取队列统计
     */
    public function getQueueStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'pending' => $query->where('send_status', NotificationQueue::STATUS_PENDING)->count(),
            'processing' => $query->where('send_status', NotificationQueue::STATUS_PROCESSING)->count(),
            'success' => $query->where('send_status', NotificationQueue::STATUS_SUCCESS)->count(),
            'failed' => $query->where('send_status', NotificationQueue::STATUS_FAILED)->count(),
            'cancelled' => $query->where('send_status', NotificationQueue::STATUS_CANCELLED)->count(),
        ];
    }

    /**
     * 获取队列类型统计
     */
    public function getQueueTypeStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->selectRaw('send_type, COUNT(*) as count')
            ->groupBy('send_type')
            ->get();
    }

    /**
     * 清理过期队列
     */
    public function cleanExpiredQueues($days = 7)
    {
        $expiredTime = time() - ($days * 24 * 3600);
        
        return $this->query()
            ->where('created_at', '<', $expiredTime)
            ->whereIn('send_status', [
                NotificationQueue::STATUS_SUCCESS,
                NotificationQueue::STATUS_FAILED,
                NotificationQueue::STATUS_CANCELLED
            ])
            ->delete();
    }
}
