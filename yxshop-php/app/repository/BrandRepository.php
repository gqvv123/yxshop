<?php

namespace app\repository;

use app\model\Brand;

/**
 * 品牌仓储类
 */
class BrandRepository extends BaseRepository
{
    protected $model = Brand::class;

    /**
     * 获取品牌列表（带商品数量）
     */
    public function getBrandsWithItemCount($appId = 0, $limit = 0)
    {
        $query = $this->query()
            ->withCount('items')
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 获取启用的品牌列表（C端用，仅 status=1）
     */
    public function getActiveBrands($appId = 0, $limit = 0)
    {
        $query = $this->query()
            ->where('status', 1)
            ->orderBy('sort', 'asc')
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 获取热门品牌
     */
    public function getHotBrands($appId = 0, $limit = 10)
    {
        $query = $this->query()
            ->where('is_hot', 1)
            ->where('status', 1)
            ->withCount('items')
            ->having('items_count', '>', 0)
            ->orderBy('items_count', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->limit($limit)->get();
    }

    /**
     * 搜索品牌
     */
    public function searchBrands($keyword, $appId = 0, $limit = 20)
    {
        $query = $this->query()
            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('sort', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        if ($limit > 0) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * 品牌统计（每项 clone 防累加 where）
     */
    public function getBrandStats($appId = 0)
    {
        $base = $this->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }
        return [
            'total'      => (clone $base)->count(),
            'hot'        => (clone $base)->where('is_hot', 1)->count(),
            'normal'     => (clone $base)->where('is_hot', 0)->count(),
            'with_items' => (clone $base)->whereHas('items')->count(),
        ];
    }

    /**
     * 后台分页（带 items 商品数，支持 is_hot 过滤）
     */
    public function paginatedWithItemCount(int $appId, int $pageSize = 20, $isHot = null)
    {
        $query = $this->query()->withCount('items');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if ($isHot !== null) {
            $query->where('is_hot', (int) $isHot);
        }
        return $query->orderBy('sort', 'asc')->paginate($pageSize);
    }

    /**
     * 批量更新（提供给 Service 用 updateWhere 替代手写 query()）
     */
    public function batchUpdateByIds(array $ids, array $data): int
    {
        return $this->updateWhere(['id' => $ids], $data);
    }

    /**
     * 批量删除
     */
    public function batchDeleteByIds(array $ids): int
    {
        return $this->deleteWhere(['id' => $ids]);
    }
}
