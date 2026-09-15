<?php

namespace app\repository;

use app\model\NotificationTemplate;

/**
 * 通知模板仓储类
 */
class NotificationTemplateRepository extends BaseRepository
{
    protected $model = NotificationTemplate::class;

    /**
     * 获取模板列表
     */
    public function getTemplates($templateType = null, $appId = 0)
    {
        $query = $this->query()
            ->orderBy('created_at', 'desc');

        if ($templateType !== null) {
            $query->where('template_type', $templateType);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取活跃模板
     */
    public function getActiveTemplates($templateType = null, $appId = 0)
    {
        $query = $this->query()
            ->where('is_active', 1)
            ->orderBy('sort', 'asc');

        if ($templateType !== null) {
            $query->where('template_type', $templateType);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 根据类型获取模板
     */
    public function getTemplatesByType($templateType, $appId = 0)
    {
        return $this->query()
            ->where('template_type', $templateType)
            ->where('is_active', 1)
            ->where('app_id', $appId)
            ->orderBy('sort', 'asc')
            ->get();
    }

    /**
     * 获取模板统计
     */
    public function getTemplateStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'active' => $query->where('is_active', 1)->count(),
            'inactive' => $query->where('is_active', 0)->count(),
            'by_type' => $query->selectRaw('template_type, COUNT(*) as count')
                ->groupBy('template_type')
                ->get(),
        ];
    }
}
