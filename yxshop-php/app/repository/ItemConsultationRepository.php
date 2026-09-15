<?php

namespace app\repository;

use app\model\ItemConsultation;

/**
 * 商品咨询仓储
 */
class ItemConsultationRepository extends BaseRepository
{
    protected $model = ItemConsultation::class;

    /**
     * 按商品获取咨询
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
     * 按用户获取咨询
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
     * 获取待回复咨询
     */
    public function getPending(int $appId = 0)
    {
        $query = $this->query()->where('status', ItemConsultation::STATUS_PENDING);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['item', 'user'])->orderBy('created_at', 'asc')->get();
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
        if (isset($filters['consult_type']) && $filters['consult_type'] !== '') {
            $query->where('consult_type', $filters['consult_type']);
        }
        if (!empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }
        return $query->with(['item', 'user', 'replier'])->paginate($pageSize);
    }

    /**
     * 回复咨询
     */
    public function reply(int $id, int $replierId, string $replyContent): ItemConsultation
    {
        $consultation = $this->findOrFail($id);
        $consultation->reply_content = $replyContent;
        $consultation->replier_id = $replierId;
        $consultation->status = ItemConsultation::STATUS_REPLIED;
        $consultation->save();
        return $consultation;
    }

    /**
     * 获取咨询统计
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
            'pending' => (clone $base)->where('status', ItemConsultation::STATUS_PENDING)->count(),
            'replied' => (clone $base)->where('status', ItemConsultation::STATUS_REPLIED)->count(),
        ];
    }
}