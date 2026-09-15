<?php

namespace app\repository;

use app\model\NotificationVariable;

/**
 * 通知变量仓储类
 */
class NotificationVariableRepository extends BaseRepository
{
    protected $model = NotificationVariable::class;

    /**
     * 获取变量列表
     */
    public function getVariables($appId = 0)
    {
        $query = $this->query()
            ->where('is_active', true)
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 根据类型获取变量
     */
    public function getVariablesByType($variableType, $appId = 0)
    {
        return $this->query()
            ->where('variable_type', $variableType)
            ->where('is_active', true)
            ->where('app_id', $appId)
            ->orderBy('sort', 'asc')
            ->get();
    }

    /**
     * 根据代码获取变量
     */
    public function getVariableByCode($variableCode, $appId = 0)
    {
        return $this->query()
            ->where('variable_code', $variableCode)
            ->where('is_active', true)
            ->where('app_id', $appId)
            ->first();
    }

    /**
     * 获取变量统计（修复累加 where bug，使用 clone）
     */
    public function getVariableStats($appId = 0)
    {
        $base = $this->query();

        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total' => (clone $base)->count(),
            'active' => (clone $base)->where('is_active', true)->count(),
            'inactive' => (clone $base)->where('is_active', false)->count(),
            'required' => (clone $base)->where('is_required', true)->count(),
            'by_type' => (clone $base)->selectRaw('variable_type, COUNT(*) as count')
                ->groupBy('variable_type')
                ->get(),
        ];
    }

    /**
     * 按 scene_id 列出变量（按 sort 升序）
     */
    public function listBySceneSorted(int $sceneId = 0)
    {
        $query = $this->query();
        if ($sceneId > 0) {
            $query->where('scene_id', $sceneId);
        }
        return $query->orderBy('sort', 'asc')->get();
    }

    /**
     * 通过场景编码 (scene_code) 获取关联变量（按 sort 升序）
     */
    public function listBySceneCode(string $sceneCode)
    {
        return $this->query()
            ->whereHas('scene', function ($query) use ($sceneCode) {
                $query->where('scene_code', $sceneCode);
            })
            ->orderBy('sort', 'asc')
            ->get();
    }
}
