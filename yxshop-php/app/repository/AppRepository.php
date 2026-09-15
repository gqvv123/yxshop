<?php

namespace app\repository;

use app\model\App;

/**
 * 应用仓储类
 */
class AppRepository extends BaseRepository
{
    protected $model = App::class;

    /**
     * 根据AppKey查找应用
     */
    public function findByAppKey(string $appKey): ?App
    {
        return $this->query()
            ->where('appkey', $appKey)
            ->first();
    }

    /**
     * 获取应用列表
     */
    public function getApps(?int $userId = null)
    {
        $query = $this->query()
            ->with(['user'])
            ->orderBy('created_at', 'desc');

        if ($userId !== null) {
            $query->where('user_id', $userId);
        }

        return $query->get();
    }

    /**
     * 获取应用统计
     * @return array{total:int, active:int, deleted:int}
     */
    public function getAppStats(): array
    {
        // 注意：deleted 计数需 withTrashed() 旁路全局 scope
        return [
            'total' => $this->query()->count(),
            'active' => $this->query()->count(),
            'deleted' => $this->query()->withTrashed()->where('deleted_at', '>', 0)->count(),
        ];
    }

    /**
     * 搜索应用
     */
    public function searchApps(string $keyword)
    {
        return $this->query()
            ->where(function ($q) use ($keyword) {
                $q->where('app_name', 'like', '%' . $keyword . '%')
                  ->orWhere('appkey', 'like', '%' . $keyword . '%');
            })
            ->with(['user'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}
