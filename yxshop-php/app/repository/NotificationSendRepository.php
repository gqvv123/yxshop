<?php

namespace app\repository;

use app\model\NotificationSend;

/**
 * 通知发送记录仓储类
 */
class NotificationSendRepository extends BaseRepository
{
    protected $model = NotificationSend::class;

    /**
     * 获取发送记录
     */
    public function getSendRecords($sceneId = null, $templateId = null, $userId = null, $sendType = null, $sendStatus = null, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->with(['scene', 'template', 'user'])
            ->orderBy('created_at', 'desc');

        if ($sceneId !== null) {
            $query->where('scene_id', $sceneId);
        }

        if ($templateId !== null) {
            $query->where('template_id', $templateId);
        }

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($sendType !== null) {
            $query->where('send_type', $sendType);
        }

        if ($sendStatus !== null) {
            $query->where('send_status', $sendStatus);
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
     * 获取发送统计
     */
    public function getSendStats($startTime = null, $endTime = null, $appId = 0)
    {
        $query = $this->query();

        if ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }

        if ($endTime) {
            $query->where('created_at', '<=', $endTime);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total_count' => $query->count(),
            'success_count' => $query->where('send_status', NotificationSend::STATUS_SUCCESS)->count(),
            'failed_count' => $query->where('send_status', NotificationSend::STATUS_FAILED)->count(),
            'pending_count' => $query->where('send_status', NotificationSend::STATUS_PENDING)->count(),
        ];
    }

    /**
     * 获取发送类型统计
     */
    public function getSendTypeStats($startTime = null, $endTime = null, $appId = 0)
    {
        $query = $this->query();

        if ($startTime) {
            $query->where('created_at', '>=', $startTime);
        }

        if ($endTime) {
            $query->where('created_at', '<=', $endTime);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->selectRaw('send_type, COUNT(*) as count')
            ->groupBy('send_type')
            ->orderBy('count', 'desc')
            ->get();
    }

    /**
     * 获取待发送记录
     */
    public function getPendingSends($sendType = null, $appId = 0, $limit = 100)
    {
        $query = $this->query()
            ->where('send_status', NotificationSend::STATUS_PENDING)
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
     * 清理过期记录
     */
    public function cleanExpiredRecords($days = 30)
    {
        $expiredTime = time() - ($days * 24 * 3600);

        return $this->query()
            ->where('created_at', '<', $expiredTime)
            ->where('send_status', NotificationSend::STATUS_SUCCESS)
            ->delete();
    }

    /**
     * 按 app 分页（后台列表）
     */
    public function getPaginatedByApp(int $appId, int $pageSize = 20)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->orderBy('id', 'desc')
            ->paginate($pageSize);
    }

    /**
     * 通用分页列表（可选 app 过滤）
     */
    public function getPaginatedList(int $appId = 0, int $pageSize = 20)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->paginate($pageSize);
    }

    /**
     * 用户收件箱（按用户 ID 分页）
     */
    public function getUserInbox(int $userId, int $page = 1, int $pageSize = 20)
    {
        return $this->query()
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 用户未读消息数（read_time = 0）
     */
    public function countUserUnread(int $userId): int
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('read_time', 0)
            ->count();
    }

    /**
     * 标记单条已读
     */
    public function markRead(int $id, int $userId): int
    {
        return $this->query()
            ->where('id', $id)
            ->where('user_id', $userId)
            ->where('read_time', 0)
            ->update(['read_time' => time(), 'updated_at' => time()]);
    }

    /**
     * 批量标记已读
     */
    public function markBatchRead(array $ids, int $userId): int
    {
        if (empty($ids)) {
            return 0;
        }
        return $this->query()
            ->whereIn('id', $ids)
            ->where('user_id', $userId)
            ->where('read_time', 0)
            ->update(['read_time' => time(), 'updated_at' => time()]);
    }
}
