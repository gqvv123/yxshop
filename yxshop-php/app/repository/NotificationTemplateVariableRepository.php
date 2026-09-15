<?php

namespace app\repository;

use app\model\NotificationTemplateVariable;

/**
 * 通知模板变量关联仓储类
 */
class NotificationTemplateVariableRepository extends BaseRepository
{
    protected $model = NotificationTemplateVariable::class;

    /**
     * 获取模板变量列表
     */
    public function getTemplateVariables($templateId = null, $variableId = null, $appId = 0)
    {
        $query = $this->query()
            ->with(['template', 'variable'])
            ->where('is_active', true)
            ->orderBy('sort', 'asc');

        if ($templateId !== null) {
            $query->where('template_id', $templateId);
        }

        if ($variableId !== null) {
            $query->where('variable_id', $variableId);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取模板的变量
     */
    public function getTemplateVariablesByTemplate($templateId, $appId = 0)
    {
        return $this->query()
            ->where('template_id', $templateId)
            ->where('is_active', true)
            ->where('app_id', $appId)
            ->with('variable')
            ->orderBy('sort', 'asc')
            ->get();
    }

    /**
     * 获取变量的模板
     */
    public function getVariableTemplatesByVariable($variableId, $appId = 0)
    {
        return $this->query()
            ->where('variable_id', $variableId)
            ->where('is_active', true)
            ->where('app_id', $appId)
            ->with('template')
            ->orderBy('sort', 'asc')
            ->get();
    }

    /**
     * 获取模板变量统计
     */
    public function getTemplateVariableStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'active' => $query->where('is_active', true)->count(),
            'inactive' => $query->where('is_active', false)->count(),
            'required' => $query->where('is_required', true)->count(),
        ];
    }
}
