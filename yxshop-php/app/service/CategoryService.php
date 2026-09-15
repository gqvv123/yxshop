<?php

namespace app\service;

use app\repository\CategoryRepository;
use app\exception\BusinessException;
use Exception;

/**
 * 商品分类服务类
 *
 * @property CategoryRepository $repository
 */
class CategoryService extends BaseService
{
    public function __construct(CategoryRepository $repository = null)
    {
        $repository = $repository ?? new CategoryRepository();
        parent::__construct($repository);
    }

    /**
     * 获取分类列表
     */
    public function getCategoryList($appId = 0)
    {
        try {
            return $this->repository->getListByApp((int) $appId);
        } catch (Exception $e) {
            $this->logError('获取分类列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取分类树
     */
    public function getCategoryTree($appId = 0)
    {
        try {
            $this->logInfo('获取分类树开始', ['app_id' => $appId]);

            $tree = $this->repository->getTree(0, $appId);

            $this->logInfo('获取分类树成功', ['app_id' => $appId]);
            return $tree;

        } catch (Exception $e) {
            $this->logError('获取分类树失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建分类
     */
    public function createCategory(array $data)
    {
        try {
            $this->logInfo('创建分类开始', ['data' => $data]);

            $this->validateWith(\app\validate\CategoryValidate::class, 'create', $data);


            if (!empty($data['parent_id']) && $data['parent_id'] != 0) {
                $parent = $this->repository->find($data['parent_id']);
                if (!$parent) {
                    throw new BusinessException('父分类不存在');
                }
                $data['level'] = $parent->level + 1;
            } else {
                $data['level'] = 1;
            }

            $category = $this->repository->create($data);

            $this->logInfo('创建分类成功', ['category_id' => $category->id]);
            return $category;

        } catch (Exception $e) {
            $this->logError('创建分类失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新分类
     */
    public function updateCategory($id, array $data)
    {
        try {
            $this->logInfo('更新分类开始', ['id' => $id, 'data' => $data]);

            $this->validateWith(\app\validate\CategoryValidate::class, 'update', $data);


            $category = $this->repository->findOrFail($id);

            // 检查是否有子分类
            if ($this->repository->hasChildren($id)) {
                throw new BusinessException('该分类下有子分类，无法修改');
            }

            $category = $this->repository->update($id, $data);

            $this->logInfo('更新分类成功', ['category_id' => $id]);
            return $category;

        } catch (Exception $e) {
            $this->logError('更新分类失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除分类
     */
    public function deleteCategory($id)
    {
        try {
            $this->logInfo('删除分类开始', ['id' => $id]);

            $category = $this->repository->findOrFail($id);

            // 检查是否有子分类
            if ($this->repository->hasChildren($id)) {
                throw new BusinessException('该分类下有子分类，无法删除');
            }

            // 检查是否有商品
            if ($this->repository->hasItems($id)) {
                throw new BusinessException('该分类下有商品，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除分类成功', ['category_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除分类失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新分类状态（启用/禁用）
     */
    public function updateCategoryStatus($id, int $status): bool
    {
        try {
            $this->logInfo('更新分类状态', ['id' => $id, 'status' => $status]);
            $this->repository->updateStatus((int) $id, $status);
            $this->logInfo('更新分类状态成功', ['id' => $id, 'status' => $status]);
            return true;
        } catch (Exception $e) {
            $this->logError('更新分类状态失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取分类详情
     */
    public function getCategoryDetail($id)
    {
        try {
            $category = $this->repository->findOrFail($id);
            $category->path = $this->repository->getCategoryPath($id);
            return $category;

        } catch (Exception $e) {
            $this->logError('获取分类详情失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取分类统计
     */
    public function getCategoryStats($appId = 0)
    {
        try {
            return $this->repository->getCategoryStats($appId);

        } catch (Exception $e) {
            $this->logError('获取分类统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 根据ID获取分类
     */
    public function getCategoryById($id)
    {
        try {
            return $this->repository->find($id);

        } catch (Exception $e) {
            $this->logError('获取分类失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPaginatedList($appId, $pageSize = 20)
    {
        return $this->repository->getPaginatedByApp((int) $appId, (int) $pageSize);
    }

    public function getTopCategories($appId)
    {
        $all = $this->repository->getListByApp((int) $appId)->toArray();
        return $this->buildTree($all);
    }

    private function buildTree(array $categories, $parentId = 0)
    {
        $tree = [];
        foreach ($categories as $cat) {
            if (($cat['parent_id'] ?? 0) == $parentId) {
                $cat['children'] = $this->buildTree($categories, $cat['id']);
                $tree[] = $cat;
            }
        }
        return $tree;
    }

    public function getRecentCategories($appId, $limit = 20)
    {
        return $this->repository->getRecentWithItemCount((int) $appId, (int) $limit);
    }
}