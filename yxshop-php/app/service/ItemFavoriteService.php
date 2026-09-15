<?php

namespace app\service;

use app\repository\ItemFavoriteRepository;

use app\exception\BusinessException;
use Exception;

/**
 * 商品收藏服务类
 *
 * @property ItemFavoriteRepository $repository
 */
class ItemFavoriteService extends BaseService
{
    protected $itemService;

    public function __construct(ItemFavoriteRepository $repository = null)
    {
        $repository = $repository ?? new ItemFavoriteRepository();
        parent::__construct($repository);
        $this->itemService = new ItemService();
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->getList($appId, $pageSize, $keyword);
    }

    /**
     * 获取用户收藏
     */
    public function getUserFavorites($userId, $appId = 0)
    {
        try {
            $this->logInfo('获取用户收藏开始', ['user_id' => $userId, 'app_id' => $appId]);

            $favorites = $this->repository->getUserFavorites($userId, $appId);

            $this->logInfo('获取用户收藏成功', ['user_id' => $userId]);
            return $favorites;

        } catch (Exception $e) {
            $this->logError('获取用户收藏失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 添加收藏
     */
    public function addFavorite($userId, $itemId, $appId = 0)
    {
        try {
            $this->logInfo('添加收藏开始', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'app_id' => $appId
            ]);

            // 检查商品是否存在
            $item = $this->itemService->find($itemId);
            if (!$item) {
                throw new BusinessException('商品不存在');
            }

            // 检查是否已收藏
            if ($this->repository->isFavorited($userId, $itemId)) {
                throw new BusinessException('商品已收藏');
            }

            $favorite = $this->repository->addFavorite($userId, $itemId, $appId);

            $this->logInfo('添加收藏成功', ['favorite_id' => $favorite->id]);
            return $favorite;

        } catch (Exception $e) {
            $this->logError('添加收藏失败', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 取消收藏
     */
    public function removeFavorite($userId, $itemId)
    {
        try {
            $this->logInfo('取消收藏开始', ['user_id' => $userId, 'item_id' => $itemId]);

            $result = $this->repository->removeFavorite($userId, $itemId);

            $this->logInfo('取消收藏成功', ['user_id' => $userId, 'item_id' => $itemId]);
            return $result;

        } catch (Exception $e) {
            $this->logError('取消收藏失败', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 切换收藏状态
     */
    public function toggleFavorite($userId, $itemId, $appId = 0)
    {
        try {
            if ($this->repository->isFavorited($userId, $itemId)) {
                return $this->removeFavorite($userId, $itemId);
            } else {
                return $this->addFavorite($userId, $itemId, $appId);
            }

        } catch (Exception $e) {
            $this->logError('切换收藏状态失败', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 检查是否已收藏
     */
    public function isFavorited($userId, $itemId)
    {
        try {
            return $this->repository->isFavorited($userId, $itemId);

        } catch (Exception $e) {
            $this->logError('检查收藏状态失败', [
                'user_id' => $userId,
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * 获取收藏统计
     */
    public function getFavoriteStats($userId, $appId = 0)
    {
        try {
            return $this->repository->getFavoriteStats($userId, $appId);

        } catch (Exception $e) {
            $this->logError('获取收藏统计失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取商品收藏统计
     */
    public function getItemFavoriteStats($itemId)
    {
        try {
            return $this->repository->getItemFavoriteStats($itemId);

        } catch (Exception $e) {
            $this->logError('获取商品收藏统计失败', [
                'item_id' => $itemId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
