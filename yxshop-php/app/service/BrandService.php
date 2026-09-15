<?php

namespace app\service;

use app\repository\BrandRepository;
use app\exception\BusinessException;
use app\validate\BrandValidate;
use Exception;

/**
 * 品牌服务类
 *
 * @property BrandRepository $repository
 */
class BrandService extends BaseService
{
    public function __construct(BrandRepository $repository = null)
    {
        $repository = $repository ?? new BrandRepository();
        parent::__construct($repository);
    }

    /**
     * 获取品牌列表
     */
    public function getBrandList($appId = 0, $limit = 0)
    {
        try {
            $this->logInfo('获取品牌列表开始', ['app_id' => $appId, 'limit' => $limit]);

            $brands = $this->repository->getBrandsWithItemCount($appId, $limit);

            $this->logInfo('获取品牌列表成功', ['app_id' => $appId]);
            return $brands;

        } catch (Exception $e) {
            $this->logError('获取品牌列表失败', [
                'app_id' => $appId,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建品牌
     */
    public function createBrand(array $data)
    {
        try {
            $this->logInfo('创建品牌开始', ['data' => $data]);

            $this->validateWith(BrandValidate::class, 'create', $data);


            $existing = $this->repository->findBy('name', $data['name']);
            if ($existing && $existing->app_id == ($data['app_id'] ?? 0)) {
                throw new BusinessException('品牌名称已存在');
            }

            $brand = $this->repository->create($data);

            $this->logInfo('创建品牌成功', ['brand_id' => $brand->id]);
            return $brand;

        } catch (Exception $e) {
            $this->logError('创建品牌失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新品牌
     */
    public function updateBrand($id, array $data)
    {
        try {
            $this->logInfo('更新品牌开始', ['id' => $id, 'data' => $data]);

            $this->validateWith(BrandValidate::class, 'update', $data);


            $currentBrand = $this->repository->find($id);
            if (!$currentBrand || $currentBrand->app_id != ($data['app_id'] ?? 0)) {
                throw new BusinessException('品牌不存在或无权修改');
            }

            if (isset($data['name'])) {
                $existing = $this->repository->findBy('name', $data['name']);
                if ($existing && $existing->id != $id && $existing->app_id == $currentBrand->app_id) {
                    throw new BusinessException('该品牌名称已被其他品牌占用');
                }
            }

            $result = $this->repository->update($id, $data);

            $this->logInfo('更新品牌成功', ['brand_id' => $id]);
            return $result;

        } catch (Exception $e) {
            $this->logError('更新品牌失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除品牌
     */
    public function deleteBrand($id)
    {
        try {
            $this->logInfo('删除品牌开始', ['id' => $id]);

            $brand = $this->repository->findOrFail($id);

            // 检查是否有商品
            if ($brand->hasItems()) {
                throw new BusinessException('该品牌下有商品，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除品牌成功', ['brand_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除品牌失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取启用的品牌列表（C端用，仅 status=1）
     */
    public function getActiveBrands($appId = 0, $limit = 12)
    {
        try {
            return $this->repository->getActiveBrands($appId, $limit);
        } catch (Exception $e) {
            $this->logError('获取启用品牌列表失败', [
                'app_id' => $appId,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取热门品牌
     */
    public function getHotBrands($appId = 0, $limit = 10)
    {
        try {
            return $this->repository->getHotBrands($appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取热门品牌失败', [
                'app_id' => $appId,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 搜索品牌
     */
    public function searchBrands($keyword, $appId = 0, $limit = 20)
    {
        try {
            return $this->repository->searchBrands($keyword, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('搜索品牌失败', [
                'keyword' => $keyword,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取品牌统计
     */
    public function getBrandStats($appId = 0)
    {
        try {
            return $this->repository->getBrandStats($appId);

        } catch (Exception $e) {
            $this->logError('获取品牌统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPaginatedList($appId, $pageSize = 20, $isHot = null)
    {
        return $this->repository->paginatedWithItemCount((int) $appId, (int) $pageSize, $isHot);
    }

    public function findById($id)
    {
        return $this->repository->findOrFail($id);
    }

    public function updateStatus($id, $status)
    {
        return $this->repository->update($id, ['status' => $status]);
    }

    public function batchUpdateStatus(array $ids, $status)
    {
        return $this->repository->batchUpdateByIds($ids, ['status' => $status]);
    }

    public function batchDelete(array $ids)
    {
        return $this->repository->batchDeleteByIds($ids);
    }
}
