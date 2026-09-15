<?php

namespace app\repository;

use app\model\Region;
use app\service\cache\TieredCache;

/**
 * 地区仓储类
 */
class RegionRepository extends BaseRepository
{
    protected $model = Region::class;

    /**
     * 获取地区树
     */
    public function getRegionTree($pid = 0)
    {
        $cacheKey = 'region_tree_' . $pid;
        $cached = TieredCache::get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $all = $this->model->newQuery()
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy('pid');

        $buildTree = function ($parentId) use (&$buildTree, &$all) {
            $children = $all->get($parentId, collect());
            foreach ($children as $region) {
                $region->children = $buildTree($region->id);
            }
            return $children;
        };

        $tree = $buildTree($pid);

        TieredCache::set($cacheKey, $tree, 86400);

        return $tree;
    }

    /**
     * 获取省份列表
     */
    public function getProvinces()
    {
        return $this->query()
            ->where('pid', 0)
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * 获取城市列表
     */
    public function getCities($provinceId)
    {
        return $this->query()
            ->where('pid', $provinceId)
            ->where('level', 2)
            ->orderBy('id', 'asc')
            ->get();
    }

    public function getDistricts($cityId)
    {
        return $this->query()
            ->where('pid', $cityId)
            ->where('level', 3)
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * 获取地区路径
     */
    public function getRegionPath($regionId)
    {
        $path = [];
        $region = $this->find($regionId);
        
        while ($region) {
            array_unshift($path, $region);
            $region = $region->pid > 0 ? $this->find($region->pid) : null;
        }
        
        return $path;
    }

    /**
     * 搜索地区
     */
    public function searchRegions($keyword)
    {
        return $this->query()
            ->where('name', 'like', '%' . $keyword . '%')
            ->orderBy('id', 'asc')
            ->get();
    }

    /**
     * 统计指定父级地区的子地区数量
     */
    public function countChildren($pid): int
    {
        return $this->model->newQuery()->where('pid', $pid)->count();
    }

    /**
     * 分页查询地区列表（全局字典表，无 app_id 字段）
     */
    public function paginateForList(int $appId = 0, int $pageSize = 20)
    {
        return $this->model->newQuery()
            ->orderBy('id', 'asc')
            ->paginate($pageSize);
    }

    /**
     * 按名称和层级查找地区
     */
    public function findByNameAndLevel(string $name, int $level, ?int $pid = null)
    {
        $query = $this->query()->where('name', $name)->where('level', $level);
        if ($pid !== null) {
            $query->where('pid', $pid);
        }
        return $query->first();
    }
}
