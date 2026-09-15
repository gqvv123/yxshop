<?php

namespace app\repository;

use app\model\DeliveryRule;

/**
 * 配送规则仓储类
 */
class DeliveryRuleRepository extends BaseRepository
{
    protected $model = DeliveryRule::class;

    /**
     * 获取配送规则列表
     */
    public function getDeliveryRules($deliveryId = null, $appId = 0)
    {
        $query = $this->query()
            ->with(['delivery'])
            ->where('rule_status', DeliveryRule::STATUS_ENABLED)
            ->orderBy('sort', 'asc');

        if ($deliveryId !== null) {
            $query->where('delivery_id', $deliveryId);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取配送模板规则
     */
    public function getDeliveryTemplateRules($deliveryId, $appId = 0)
    {
        return $this->query()
            ->where('delivery_id', $deliveryId)
            ->where('rule_status', DeliveryRule::STATUS_ENABLED)
            ->where('app_id', $appId)
            ->orderBy('sort', 'asc')
            ->get();
    }

    /**
     * 根据条件匹配配送规则
     */
    public function matchDeliveryRule($deliveryId, $condition, $value, $areas = [], $appId = 0)
    {
        $query = $this->query()
            ->where('delivery_id', $deliveryId)
            ->where('rule_status', DeliveryRule::STATUS_ENABLED)
            ->where('rule_condition', $condition);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        // 如果指定了区域，检查规则是否适用该区域
        if (!empty($areas)) {
            $query->where(function($q) use ($areas) {
                $q->whereNull('rule_areas')
                  ->orWhereJsonContains('rule_areas', $areas);
            });
        }

        return $query->orderBy('sort', 'asc')->get();
    }

    /**
     * 获取配送规则统计
     */
    public function getDeliveryRuleStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'enabled' => $query->where('rule_status', DeliveryRule::STATUS_ENABLED)->count(),
            'disabled' => $query->where('rule_status', DeliveryRule::STATUS_DISABLED)->count(),
            'by_type' => $query->selectRaw('rule_type, COUNT(*) as count')
                ->groupBy('rule_type')
                ->get(),
        ];
    }

    /**
     * 清除指定 app 下的默认模板/规则标记
     */
    public function clearDefaultForApp(int $appId, $excludeId = null): int
    {
        $query = $this->model->newQuery()
            ->where('app_id', $appId)
            ->where('is_default', 1);

        if ($excludeId !== null) {
            $query->where('delivery_id', '!=', $excludeId);
        }

        return $query->update(['is_default' => 0]);
    }

    /**
     * 规则列表分页（带过滤）
     */
    public function paginateRules(int $page, int $limit, array $filters = [])
    {
        $query = $this->model->newQuery()->orderBy('created_at', 'desc');

        if (!empty($filters['app_id'])) {
            $query->where('app_id', $filters['app_id']);
        }

        if (!empty($filters['rule_name'])) {
            $query->where('name', 'like', '%' . $filters['rule_name'] . '%');
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($limit, ['*'], 'page', $page);
    }
}
