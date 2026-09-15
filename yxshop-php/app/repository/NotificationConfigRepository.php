<?php

namespace app\repository;

use app\model\NotificationConfig;

/**
 * 通知配置仓储类
 */
class NotificationConfigRepository extends BaseRepository
{
    protected $model = NotificationConfig::class;

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->paginate($pageSize);
    }

    /**
     * 获取配置列表
     */
    public function getConfigs($configType = null, $appId = 0)
    {
        $query = $this->query()
            ->where('is_active', true)
            ->orderBy('sort', 'asc');

        if ($configType !== null) {
            $query->where('config_type', $configType);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 根据类型获取配置
     */
    public function getConfigsByType($configType, $appId = 0)
    {
        return $this->query()
            ->where('config_type', $configType)
            ->where('is_active', true)
            ->where('app_id', $appId)
            ->orderBy('sort', 'asc')
            ->get();
    }

    /**
     * 根据键获取配置
     */
    public function getConfigByKey($configKey, $appId = 0)
    {
        return $this->query()
            ->where('config_key', $configKey)
            ->where('is_active', true)
            ->where('app_id', $appId)
            ->first();
    }

    /**
     * 获取配置统计
     */
    public function getConfigStats($appId = 0)
    {
        $query = $this->query();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => $query->count(),
            'active' => $query->where('is_active', true)->count(),
            'inactive' => $query->where('is_active', false)->count(),
            'by_type' => $query->selectRaw('config_type, COUNT(*) as count')
                ->groupBy('config_type')
                ->get(),
        ];
    }
}
