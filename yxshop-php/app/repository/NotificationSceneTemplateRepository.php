<?php

namespace app\repository;

use app\model\NotificationSceneTemplate;

/**
 * 通知场景模板关联仓储类
 */
class NotificationSceneTemplateRepository extends BaseRepository
{
    protected $model = NotificationSceneTemplate::class;

    /**
     * 获取场景模板列表
     */
    public function getSceneTemplates($sceneId = null, $templateId = null, $appId = 0)
    {
        $query = $this->query()
            ->with(['scene', 'template'])
            ->where('is_active', true)
            ->orderBy('sort', 'asc');

        if ($sceneId !== null) {
            $query->where('scene_id', $sceneId);
        }

        if ($templateId !== null) {
            $query->where('template_id', $templateId);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取场景的模板
     */
    public function getSceneTemplatesByScene($sceneId, $appId = 0)
    {
        return $this->query()
            ->where('scene_id', $sceneId)
            ->where('is_active', true)
            ->where('app_id', $appId)
            ->with('template')
            ->orderBy('sort', 'asc')
            ->get();
    }

    /**
     * 获取模板的场景
     */
    public function getTemplateScenesByTemplate($templateId, $appId = 0)
    {
        return $this->query()
            ->where('template_id', $templateId)
            ->where('is_active', true)
            ->where('app_id', $appId)
            ->with('scene')
            ->orderBy('sort', 'asc')
            ->get();
    }

    /**
     * 获取场景模板统计
     */
    public function getSceneTemplateStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'active' => $query->where('is_active', true)->count(),
            'inactive' => $query->where('is_active', false)->count(),
        ];
    }
}
