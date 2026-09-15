<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\ItemFavoriteService;

class FavoriteController extends BaseController
{
    /** @var ItemFavoriteService */
    protected $favoriteService;

    public function __construct()
    {
        $this->favoriteService = new ItemFavoriteService();
    }

    /**
     * 检查是否已收藏
     */
    public function check(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $itemId = $request->get('item_id') ?? $request->get('target_id');
        if (!$itemId) {
            return $this->error('商品ID不能为空');
        }
        $favorited = $this->favoriteService->isFavorited($userId, $itemId);
        return $this->success(['is_favorited' => $favorited]);
    }

    /**
     * 添加收藏
     */
    public function add(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $itemId = $request->post('item_id') ?? $request->post('target_id');
        if (!$itemId) {
            return $this->error('商品ID不能为空');
        }
        $appId = (int) ($request->post('app_id') ?? ($request->user->app_id ?? 0));

        $favorite = $this->favoriteService->addFavorite($userId, $itemId, $appId);
        return $this->success($favorite, '收藏成功');
    }

    /**
     * 取消收藏
     */
    public function remove(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $itemId = $request->post('item_id') ?? $request->post('target_id');
        if (!$itemId) {
            return $this->error('商品ID不能为空');
        }
        $this->favoriteService->removeFavorite($userId, $itemId);
        return $this->success(null, '已取消收藏');
    }

    /**
     * 获取收藏列表
     */
    public function getList(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $appId = (int) ($request->get('app_id') ?? ($request->user->app_id ?? 0));
        $list = $this->favoriteService->getUserFavorites($userId, $appId);
        return $this->success($list);
    }
}
