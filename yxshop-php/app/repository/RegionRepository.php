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

        // 说明：地区表的 pid 在历史数据里可能为 NULL，
        // 直接 groupBy('pid') 会用 null 当数组键，PHP 8.5 下触发
        // "Using null as an array offset is deprecated"，
        // 而 webman 的错误处理器会把 deprecation 升级为 ErrorException，
        // 导致整个建树过程失败（接口返回空）。
        // 这里统一用 (int) 归一化，NULL 一律按 0（顶级）处理。
        $all = $this->model->newQuery()
            ->orderBy('id', 'asc')
            ->get()
            ->groupBy(static function ($region) {
                return (int) ($region->pid ?? 0);
            });

        $buildTree = function ($parentId) use (&$buildTree, &$all) {
            $children = $all->get((int) $parentId, collect());
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
