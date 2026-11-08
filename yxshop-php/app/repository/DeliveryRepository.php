<?php

namespace app\repository;

use app\model\Delivery;

/**
 * 数据仓储：Delivery Repository。
 *
 * @package app\repository
 */
class DeliveryRepository extends BaseRepository
{
    protected $model = Delivery::class;

    public function getDeliveries($appId = 0)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) $query->where('app_id', $appId);
        return $query->get();
    }

    public function paginateForList(int $appId = 0, int $pageSize = 20, string $keyword = '')
    {
        $query = $this->model->newQuery()
            ->withCount('rules')
            ->orderBy('created_at', 'desc');

        if ($appId > 0) $query->where('app_id', $appId);
        if ($keyword !== '') $query->where('name', 'like', '%' . $keyword . '%');

        return $query->paginate($pageSize);
    }

    /**
     * C 端可用配送方式：只返回启用中、且有名称的模板
     *
     * 管理端列表需要看到停用项（便于重新启用），所以不能复用 paginateForList。
     * 空名模板属于无效配置，一并排除——否则前端会兜底成多个同名「默认配送」。
     *
     * @param int $appId
     * @param int $limit
     * @return array
     */
    public function getActiveForClient(int $appId = 0, int $limit = 50): array
    {
        $query = $this->model->newQuery()
            ->withCount('rules')
            ->where('status', 1)
            ->where('name', '<>', '')
            ->orderBy('sort', 'asc')
            ->limit($limit);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->get()->toArray();
    }

    public function getDeliveryStats($appId = 0): array
    {
        $base = $this->model->newQuery();
        if ($appId > 0) $base->where('app_id', $appId);
        return ['total' => (clone $base)->count()];
    }
}
