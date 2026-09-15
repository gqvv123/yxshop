<?php

namespace app\repository;

use app\model\Advertisement;

/**
 * 广告仓储类
 */
class AdvertisementRepository extends BaseRepository
{
    protected $model = Advertisement::class;

    /**
     * 获取有效广告
     */
    public function getActiveAds($position = null, $appId = 0)
    {
        $now = time();
        $query = $this->query()
            ->where('is_show', 1)
            ->where('start_time', '<=', $now)
            ->where('end_time', '>=', $now)
            ->orderBy('sort', 'asc');

        if ($position !== null) {
            $query->where('position', $position);
        }

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取即将开始的广告
     */
    public function getUpcomingAds($appId = 0, $days = 7)
    {
        $startTime = time();
        $endTime = time() + ($days * 24 * 3600);

        $query = $this->query()
            ->where('is_show', 1)
            ->where('start_time', '>', $startTime)
            ->where('start_time', '<=', $endTime)
            ->orderBy('start_time', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取已过期的广告
     */
    public function getExpiredAds($appId = 0)
    {
        $now = time();
        $query = $this->query()
            ->where('end_time', '<', $now)
            ->orderBy('end_time', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get();
    }

    /**
     * 获取广告统计
     */
    public function getAdStats($appId = 0)
    {
        $query = $this->query();
        $now = time();

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return [
            'total' => (clone $query)->count(),
            'active' => (clone $query)->where('is_show', 1)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>=', $now)
                ->count(),
            'upcoming' => (clone $query)->where('is_show', 1)
                ->where('start_time', '>', $now)
                ->count(),
            'expired' => (clone $query)->where('end_time', '<', $now)
                ->count(),
        ];
    }

    /**
     * 按 app + 可选状态获取广告列表
     *
     * @param int      $appId
     * @param int|null $status  is_show 状态
     */
    public function listForAdmin(int $appId = 0, $status = null)
    {
        $query = $this->model->newQuery()->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($status !== null) {
            $query->where('is_show', $status);
        }

        return $query->get();
    }

    /**
     * 按 app + keyword 分页查询广告列表
     */
    public function paginateForList(int $appId = 0, int $pageSize = 20, string $keyword = '')
    {
        $query = $this->model->newQuery()->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($keyword !== '') {
            $query->where('title', 'like', '%' . $keyword . '%');
        }

        return $query->paginate($pageSize);
    }
}
