<?php

namespace app\repository;

use app\model\RechargePackage;

/**
 * 充值套餐仓储
 */
class RechargePackageRepository extends BaseRepository
{
    protected $model = RechargePackage::class;

    /**
     * 获取已启用的套餐
     */
    public function getEnabled(int $appId = 0)
    {
        $query = $this->query()->where('status', RechargePackage::STATUS_ENABLED);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }

    /**
     * 后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        $query = $this->query()->orderBy('sort', 'asc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['keyword'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['keyword']);
            $query->where('package_name', 'like', '%' . $escaped . '%');
        }
        return $query->paginate($pageSize);
    }

    /**
     * 获取套餐统计
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
            'enabled' => (clone $base)->where('status', RechargePackage::STATUS_ENABLED)->count(),
            'disabled' => (clone $base)->where('status', RechargePackage::STATUS_DISABLED)->count(),
        ];
    }
}