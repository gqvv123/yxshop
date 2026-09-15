<?php

namespace app\repository;

use app\model\ParamTemplate;

/**
 * 参数模板仓储
 */
class ParamTemplateRepository extends BaseRepository
{
    protected $model = ParamTemplate::class;

    /**
     * 获取已启用的模板
     */
    public function getEnabled(int $appId = 0)
    {
        $query = $this->query()->where('status', ParamTemplate::STATUS_ENABLED);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['category'])->orderBy('sort', 'asc')->get();
    }

    /**
     * 按分类获取模板
     */
    public function getByCategory(int $categoryId, int $appId = 0)
    {
        $query = $this->query()->where('category_id', $categoryId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->with(['items'])->orderBy('sort', 'asc')->get();
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
        if (!empty($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }
        if (!empty($filters['keyword'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['keyword']);
            $query->where('template_name', 'like', '%' . $escaped . '%');
        }
        return $query->with(['category', 'items'])->paginate($pageSize);
    }

    /**
     * 获取模板统计
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
            'enabled' => (clone $base)->where('status', ParamTemplate::STATUS_ENABLED)->count(),
            'disabled' => (clone $base)->where('status', ParamTemplate::STATUS_DISABLED)->count(),
        ];
    }
}