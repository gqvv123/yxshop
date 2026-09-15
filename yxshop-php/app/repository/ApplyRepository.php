<?php

namespace app\repository;

use app\model\Apply;

/**
 * 申请仓储类
 */
class ApplyRepository extends BaseRepository
{
    protected $model = Apply::class;

    /**
     * 获取申请列表
     */
    public function getApplies($userId = null, $applyType = null, $applyStatus = null, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->with(['user', 'auditor'])
            ->orderBy('created_at', 'desc');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        if ($applyType !== null) {
            $query->where('apply_type', $applyType);
        }

        if ($applyStatus !== null) {
            $query->where('apply_status', $applyStatus);
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
     * 获取待审核申请
     */
    public function getPendingApplies($applyType = null, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->where('apply_status', Apply::STATUS_PENDING)
            ->with(['user'])
            ->orderBy('created_at', 'asc');

        if ($applyType !== null) {
            $query->where('apply_type', $applyType);
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
     * 获取申请统计
     */
    public function getApplyStats($startTime = null, $endTime = null, $appId = 0)
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
            'total_count' => (clone $query)->count(),
            'by_type' => (clone $query)->selectRaw('apply_type, COUNT(*) as count')
                ->groupBy('apply_type')
                ->get(),
            'by_status' => (clone $query)->selectRaw('apply_status, COUNT(*) as count')
                ->groupBy('apply_status')
                ->get(),
        ];
    }

    /**
     * 获取用户申请统计
     */
    public function getUserApplyStats($userId, $appId = 0)
    {
        $base = $this->query()->where('user_id', $userId);

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total_count' => (clone $base)->count(),
            'pending_count' => (clone $base)->where('apply_status', Apply::STATUS_PENDING)->count(),
            'approved_count' => (clone $base)->where('apply_status', Apply::STATUS_APPROVED)->count(),
            'rejected_count' => (clone $base)->where('apply_status', Apply::STATUS_REJECTED)->count(),
            'processing_count' => (clone $base)->where('apply_status', Apply::STATUS_PROCESSING)->count(),
            'completed_count' => (clone $base)->where('apply_status', Apply::STATUS_COMPLETED)->count(),
        ];
    }
}
