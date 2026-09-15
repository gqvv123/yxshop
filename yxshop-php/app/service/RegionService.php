<?php

namespace app\service;

use app\repository\RegionRepository;
use app\exception\BusinessException;
use Exception;

/**
 * 地区服务类
 *
 * @property RegionRepository $repository
 */
class RegionService extends BaseService
{
    public function __construct(?RegionRepository $repository = null)
    {
        parent::__construct($repository ?? new RegionRepository());
    }

    /**
     * 获取地区树
     */
    public function getRegionTree($pid = 0)
    {
        try {
            $this->logInfo('获取地区树开始');

            $tree = $this->repository->getRegionTree($pid);

            $this->logInfo('获取地区树成功');
            return $tree;

        } catch (Exception $e) {
            $this->logError('获取地区树失败', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取省份列表
     */
    public function getProvinces()
    {
        try {
            return $this->repository->getProvinces();

        } catch (Exception $e) {
            $this->logError('获取省份列表失败', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取城市列表
     */
    public function getCities($provinceId)
    {
        try {
            $this->logInfo('获取城市列表开始', ['province_id' => $provinceId]);

            $cities = $this->repository->getCities($provinceId);

            $this->logInfo('获取城市列表成功', ['province_id' => $provinceId]);
            return $cities;

        } catch (Exception $e) {
            $this->logError('获取城市列表失败', [
                'province_id' => $provinceId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取区县列表
     */
    public function getDistricts($cityId)
    {
        try {
            $this->logInfo('获取区县列表开始', ['city_id' => $cityId]);

            $districts = $this->repository->getDistricts($cityId);

            $this->logInfo('获取区县列表成功', ['city_id' => $cityId]);
            return $districts;

        } catch (Exception $e) {
            $this->logError('获取区县列表失败', [
                'city_id' => $cityId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取地区路径
     */
    public function getRegionPath($regionId)
    {
        try {
            return $this->repository->getRegionPath($regionId);

        } catch (Exception $e) {
            $this->logError('获取地区路径失败', [
                'region_id' => $regionId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 搜索地区
     */
    public function searchRegions($keyword)
    {
        try {
            $this->logInfo('搜索地区开始', ['keyword' => $keyword]);

            $regions = $this->repository->searchRegions($keyword);

            $this->logInfo('搜索地区成功', ['keyword' => $keyword]);
            return $regions;

        } catch (Exception $e) {
            $this->logError('搜索地区失败', [
                'keyword' => $keyword,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建地区
     */
    public function createRegion(array $data)
    {
        try {
            $this->logInfo('创建地区开始', ['data' => $data]);



            $region = $this->repository->create($data);

            $this->logInfo('创建地区成功', ['region_id' => $region->id]);
            return $region;

        } catch (Exception $e) {
            $this->logError('创建地区失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新地区
     */
    public function updateRegion($id, array $data)
    {
        try {
            $this->logInfo('更新地区开始', ['id' => $id, 'data' => $data]);

            $region = $this->repository->update($id, $data);

            $this->logInfo('更新地区成功', ['region_id' => $id]);
            return $region;

        } catch (Exception $e) {
            $this->logError('更新地区失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除地区
     */
    public function deleteRegion($id)
    {
        try {
            $this->logInfo('删除地区开始', ['id' => $id]);

            $region = $this->repository->findOrFail($id);

            // 检查是否有子地区
            $children = $this->repository->countChildren($id);

            if ($children > 0) {
                throw new BusinessException('该地区下有子地区，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除地区成功', ['region_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除地区失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->paginateForList((int) $appId, (int) $pageSize);
    }

    /**
     * 按名称和层级查找地区（用于解析小程序 picker 返回的文本名称）
     */
    public function findByNameAndLevel(string $name, int $level, ?int $pid = null)
    {
        return $this->repository->findByNameAndLevel($name, $level, $pid);
    }

    /**
     * 解析地址数据中的地区文本名称为 region_id
     * 适用于小程序 picker 返回的 province/city/district 文本
     */
    public function resolveRegionIds(array $data): array
    {
        if (!empty($data['province']) && empty($data['province_id'])) {
            $row = $this->repository->findByNameAndLevel($data['province'], 1);
            if ($row) {
                $data['province_id'] = $row->id;
            }
        }

        if (!empty($data['city']) && empty($data['city_id'])) {
            $pid = $data['province_id'] ?? null;
            $row = $this->repository->findByNameAndLevel($data['city'], 2, $pid);
            if ($row) {
                $data['city_id'] = $row->id;
            }
        }

        if (!empty($data['district']) && empty($data['district_id'])) {
            $pid = $data['city_id'] ?? null;
            $row = $this->repository->findByNameAndLevel($data['district'], 3, $pid);
            if ($row) {
                $data['district_id'] = $row->id;
            }
        }

        $data['province_id'] = $data['province_id'] ?? 0;
        $data['city_id']     = $data['city_id'] ?? 0;
        $data['district_id'] = $data['district_id'] ?? 0;

        return $data;
    }
}
