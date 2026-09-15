<?php

namespace app\repository;

use app\model\Theme;
use Illuminate\Database\Eloquent\Collection;

/**
 * 前台主题仓储类
 */
class ThemeRepository extends BaseRepository
{
    protected $model = Theme::class;

    /**
     * 获取所有未删除主题
     */
    public function getActiveList(): Collection
    {
        return $this->query()
            ->where('deleted_at', 0)
            ->orderBy('is_active', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * 获取当前激活主题
     */
    public function getCurrentActive(int $appId): ?Theme
    {
        $query = $this->query()
            ->where('app_id', $appId)
            ->where('is_active', 1)
            ->where('deleted_at', 0);

        $theme = $query->first();

        if (!$theme) {
            // 回退到默认主题
            $theme = $this->query()
                ->where('app_id', 0)
                ->where('is_active', 1)
                ->where('deleted_at', 0)
                ->first();
        }

        return $theme;
    }

    /**
     * 查找未删除主题
     */
    public function findActive(int $themeId): ?Theme
    {
        return $this->query()
            ->where('id', $themeId)
            ->where('deleted_at', 0)
            ->first();
    }

    /**
     * 激活指定主题（同 app 下其他主题取消激活）
     */
    public function activate(int $themeId, int $appId): void
    {
        $this->query()
            ->where('app_id', $appId)
            ->where('is_active', 1)
            ->update(['is_active' => 0, 'updated_at' => time()]);

        $this->query()
            ->where('id', $themeId)
            ->update(['is_active' => 1, 'updated_at' => time()]);
    }

    /**
     * 更新主题自定义字段
     */
    public function updateCustom(int $themeId, int $appId, array $data): int
    {
        return $this->query()
            ->where('id', $themeId)
            ->where('app_id', $appId)
            ->where('deleted_at', 0)
            ->update(array_merge($data, ['updated_at' => time()]));
    }

    /**
     * 软删除主题
     */
    public function softDelete(int $themeId): int
    {
        return $this->query()
            ->where('id', $themeId)
            ->update(['deleted_at' => time(), 'updated_at' => time()]);
    }
}
