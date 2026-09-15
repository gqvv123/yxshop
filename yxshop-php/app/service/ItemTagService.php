<?php

namespace app\service;

use app\repository\ItemTagRepository;
use app\model\ItemTag;
use app\exception\BusinessException;
use Exception;

/**
 * 商品标签服务类
 *
 * @property ItemTagRepository $repository
 */
class ItemTagService extends BaseService
{
    public function __construct(?ItemTagRepository $repository = null)
    {
        parent::__construct($repository ?? new ItemTagRepository());
    }

    /**
     * 获取标签列表
     */
    public function getTagList($appId = 0)
    {
        try {
            $this->logInfo('获取标签列表开始', ['app_id' => $appId]);

            $tags = $this->repository->getTags($appId);

            $this->logInfo('获取标签列表成功', ['app_id' => $appId]);
            return $tags;

        } catch (Exception $e) {
            $this->logError('获取标签列表失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建标签
     */
    public function createTag(array $data)
    {
        try {
            $this->logInfo('创建标签开始', ['data' => $data]);


            // 检查标签名称是否重复
            $existing = $this->repository->findByNameAndApp($data['tag_name'], $data['app_id']);

            if ($existing) {
                throw new BusinessException('标签名称已存在');
            }

            $tag = $this->repository->create($data);

            $this->logInfo('创建标签成功', ['tag_id' => $tag->id]);
            return $tag;

        } catch (Exception $e) {
            $this->logError('创建标签失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新标签
     */
    public function updateTag($id, array $data)
    {
        try {
            $this->logInfo('更新标签开始', ['id' => $id, 'data' => $data]);


            $tag = $this->repository->findOrFail($id);

            // 检查标签名称是否重复
            if (isset($data['tag_name'])) {
                $existing = $this->repository->findByNameAndApp($data['tag_name'], $tag->app_id, $id);

                if ($existing) {
                    throw new BusinessException('标签名称已存在');
                }
            }

            $tag = $this->repository->update($id, $data);

            $this->logInfo('更新标签成功', ['tag_id' => $id]);
            return $tag;

        } catch (Exception $e) {
            $this->logError('更新标签失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除标签
     */
    public function deleteTag($id)
    {
        try {
            $this->logInfo('删除标签开始', ['id' => $id]);

            $tag = $this->repository->findOrFail($id);

            // 检查是否有商品使用该标签
            if ($tag->items()->count() > 0) {
                throw new BusinessException('该标签有商品使用，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除标签成功', ['tag_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除标签失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取热门标签
     */
    public function getHotTags($appId = 0, $limit = 10)
    {
        try {
            return $this->repository->getHotTags($appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取热门标签失败', [
                'app_id' => $appId,
                'limit' => $limit,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 搜索标签
     */
    public function searchTags($keyword, $appId = 0)
    {
        try {
            return $this->repository->searchTags($keyword, $appId);

        } catch (Exception $e) {
            $this->logError('搜索标签失败', [
                'keyword' => $keyword,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取标签统计
     */
    public function getTagStats($appId = 0)
    {
        try {
            return $this->repository->getTagStats($appId);

        } catch (Exception $e) {
            $this->logError('获取标签统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->paginatedForAdmin((int) $appId, (int) $pageSize, (string) $keyword);
    }
}
