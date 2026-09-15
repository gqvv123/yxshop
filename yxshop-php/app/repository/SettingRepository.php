<?php

namespace app\repository;

use app\model\Setting;

/**
 * 系统设置仓储类
 */
class SettingRepository extends BaseRepository
{
    protected $model = Setting::class;

    /**
     * 根据键获取设置
     */
    public function getByKey($key, $appId = 0)
    {
        return $this->query()
            ->where('key', $key)
            ->where('app_id', $appId)
            ->first();
    }

    /**
     * 根据分组获取设置
     */
    public function getByGroup($group, $appId = 0)
    {
        return $this->query()
            ->where('group', $group)
            ->where('app_id', $appId)
            ->get();
    }

    /**
     * 获取所有设置
     */
    public function getAllSettings($appId = 0)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->get()
            ->keyBy('key');
    }

    /**
     * 按分组和键查询设置列表（跨应用）
     */
    public function getByGroupAndKey(string $group, string $key)
    {
        return $this->query()
            ->where('group', $group)
            ->where('key', $key)
            ->get();
    }

    /**
     * 设置值
     */
    public function setValue($key, $value, $type = 'string', $group = 'system', $description = '', $appId = 0, $isEncrypted = 0)
    {
        $setting = $this->getByKey($key, $appId);

        if (!$setting) {
            $setting = new Setting();
            $setting->key = $key;
            $setting->type = $type;
            $setting->group = $group;
            $setting->description = $description;
            $setting->app_id = $appId;
        }

        $setting->is_encrypted = $isEncrypted;

        switch ($type) {
            case 'json':
                $setting->value = json_encode($value);
                break;
            default:
                $setting->value = (string) $value;
                break;
        }

        $setting->save();
        return $setting;
    }

    /**
     * 获取设置统计
     */
    public function getSettingStats($appId = 0)
    {
        $base = $this->query()->where('app_id', $appId);

        return [
            'total' => (clone $base)->count(),
            'by_group' => (clone $base)->selectRaw('`group`, COUNT(*) as count')
                ->groupBy('group')
                ->get(),
        ];
    }

    /**
     * 按 (id, group) 精确取一条
     */
    public function findByIdAndGroup($id, string $group)
    {
        return $this->query()
            ->where('id', $id)
            ->where('group', $group)
            ->first();
    }

    /**
     * 取某分组下全部记录（按 sort 升序）
     */
    public function getByGroupSorted(string $group): array
    {
        return $this->query()
            ->where('group', $group)
            ->orderBy('sort')
            ->get()
            ->toArray();
    }
}
