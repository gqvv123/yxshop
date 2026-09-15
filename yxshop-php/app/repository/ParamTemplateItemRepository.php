<?php

namespace app\repository;

use app\model\ParamTemplateItem;

/**
 * 参数模板项仓储
 */
class ParamTemplateItemRepository extends BaseRepository
{
    protected $model = ParamTemplateItem::class;

    /**
     * 按模板获取参数项
     */
    public function getByTemplate(int $templateId, int $appId = 0)
    {
        $query = $this->query()->where('template_id', $templateId);
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
        if (!empty($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }
        if (!empty($filters['param_type'])) {
            $query->where('param_type', $filters['param_type']);
        }
        if (!empty($filters['keyword'])) {
            $escaped = str_replace(['%', '_'], ['\\%', '\\_'], $filters['keyword']);
            $query->where('param_name', 'like', '%' . $escaped . '%');
        }
        return $query->with(['template'])->paginate($pageSize);
    }
}