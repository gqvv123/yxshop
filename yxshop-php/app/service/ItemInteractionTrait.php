<?php

namespace app\service;
use app\exception\BusinessException;
use Exception;

/**
 * ItemInteractionTrait：从 ItemService.php 抽离的getHotItems、getRecommendedItems、getNewItems、addFavorite、removeFavorite、getUserFavorites、isFavorited、recordSearch、recordView等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait ItemInteractionTrait
{
    public function getHotItems($limit = 10, $appId = 0)
    {
        try {
            return $this->repository->getHotItems((int) $limit, (int) $appId);
        } catch (Exception $e) {
            $this->logError('获取热门商品失败', ['limit' => $limit, 'app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getRecommendedItems($limit = 10, $appId = 0)
    {
        try {
            return $this->repository->getRecommendedItems((int) $limit, (int) $appId);
        } catch (Exception $e) {
            $this->logError('获取推荐商品失败', ['limit' => $limit, 'app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getNewItems($limit = 10, $appId = 0)
    {
        try {
            return $this->repository->getNewItems((int) $limit, (int) $appId);
        } catch (Exception $e) {
            $this->logError('获取新品失败', ['limit' => $limit, 'app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function addFavorite($itemId, $userId, $appId): bool
    {
        try {
            $this->logInfo('添加收藏开始', ['item_id' => $itemId, 'user_id' => $userId]);
            $this->repository->findOrFail($itemId);

            if ($this->favoriteRepository->isFavorited($userId, $itemId)) {
                throw new BusinessException('商品已收藏');
            }
            $this->favoriteRepository->addFavorite($userId, $itemId, $appId);

            $this->logInfo('添加收藏成功', ['item_id' => $itemId, 'user_id' => $userId]);
            return true;
        } catch (Exception $e) {
            $this->logError('添加收藏失败', ['item_id' => $itemId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function removeFavorite($itemId, $userId): bool
    {
        try {
            $this->favoriteRepository->removeFavorite($userId, $itemId);
            return true;
        } catch (Exception $e) {
            $this->logError('取消收藏失败', ['item_id' => $itemId, 'user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getUserFavorites($userId, $page = 1, $pageSize = 20)
    {
        try {
            return $this->favoriteRepository->getUserFavoritesPaginated((int) $userId, (int) $page, (int) $pageSize);
        } catch (Exception $e) {
            $this->logError('获取用户收藏列表失败', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function isFavorited($itemId, $userId)
    {
        return $this->favoriteRepository->isFavorited($userId, $itemId);
    }

    private function recordSearch($keyword, $userId, $appId)
    {
        try {
            // 解码 URL 编码的搜索词（小程序端可能 encode 后传入）
            $keyword = urldecode($keyword);
            if ($keyword === '') {
                return;
            }

            $this->searchRepository->create([
                'user_id'       => $userId ?: 0,
                'keyword'       => $keyword,
                'app_id'        => $appId ?: 0,
                'ip'            => request()->getRemoteIp() ?? '',
                'result_count'  => 0,
                'created_at'    => time(),
            ]);
        } catch (Exception $e) {
            $this->logError('记录搜索失败', ['keyword' => $keyword, 'user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }

    private function recordView($itemId, $userId, $appId): void
    {
        try {
            $this->viewRepository->create([
                'user_id'    => $userId,
                'item_id'    => $itemId,
                'app_id'     => $appId,
                'created_at' => time(),
            ]);
        } catch (Exception $e) {
            $this->logError('记录浏览失败', ['item_id' => $itemId, 'user_id' => $userId, 'error' => $e->getMessage()]);
        }
    }
}
