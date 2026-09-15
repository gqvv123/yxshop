<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\UserService;
use app\service\ItemService;

class UserController extends BaseController
{
    protected $userService;
    protected $itemService;
    
    public function __construct()
    {
        $this->userService = new UserService();
        $this->itemService = new ItemService();
    }
    
    /**
     * 获取用户信息
     */
    public function getInfo(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        $result = $this->userService->getUserInfo($userId);
        
        if ($result['success']) {
            return $this->success($result['data']);
        } else {
            return $this->error($result['message']);
        }
    }

    /**
     * 用户综合信息（个人中心首页聚合）
     *
     * GET /api/v1/user/profile
     * 返回：基础信息+等级+余额+积分+优惠券数+订单状态数+收藏数+足迹数
     */
    public function profile(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        try {
            $result = $this->userService->getUserProfile((int) $userId);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * 更新用户信息
     */
    public function updateInfo(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $data = $request->post();
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        $result = $this->userService->updateUserInfo($userId, $data);
        
        if ($result['success']) {
            return $this->success(null, $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }
    
    /**
     * 更新用户头像
     */
    public function updateAvatar(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $avatar = $request->file('avatar');
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        if (!$avatar) {
            return $this->error('请选择头像文件');
        }
        
        $result = $this->userService->updateUserAvatar($userId, $avatar);
        
        if ($result['success']) {
            return $this->success($result['data'], $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }
    
    /**
     * 修改密码
     */
    public function changePassword(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $data = $request->post();
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        if (empty($data['old_password'])) {
            return $this->error('原密码不能为空');
        }
        
        if (empty($data['new_password'])) {
            return $this->error('新密码不能为空');
        }
        
        $result = $this->userService->changePassword($userId, $data['old_password'], $data['new_password']);
        
        if ($result['success']) {
            return $this->success(null, $result['message']);
        } else {
            return $this->error($result['message']);
        }
    }
    
    /**
     * 获取用户订单
     */
    public function getOrders(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 20);
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        $result = $this->userService->getUserOrders($userId, $page, $pageSize);
        
        if ($result['success']) {
            return $this->success($result['data']);
        } else {
            return $this->error($result['message']);
        }
    }
    
    /**
     * 获取用户收藏
     */
    public function getFavorites(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $page = $request->get('page', 1);
        $pageSize = $request->get('page_size', 20);
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        $result = $this->userService->getUserFavorites($userId, $page, $pageSize);
        
        if ($result['success']) {
            return $this->success($result['data']);
        } else {
            return $this->error($result['message']);
        }
    }

    /**
     * 用户浏览足迹（分页）
     *
     * GET /api/v1/user/footprint?page=1&page_size=20
     */
    public function footprint(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);

        try {
            $result = $this->userService->getFootprint((int) $userId, $page, $pageSize);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除单条浏览足迹
     *
     * POST /api/v1/user/footprint-remove  view_id=1
     */
    public function footprintRemove(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $viewId = $request->post('view_id', '');
        if (empty($viewId)) {
            return $this->error('足迹ID不能为空');
        }

        try {
            $this->userService->removeFootprint($viewId, (int) $userId);
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 清空浏览足迹
     *
     * POST /api/v1/user/footprint-clear
     */
    public function footprintClear(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        try {
            $this->userService->clearFootprint((int) $userId);
            return $this->success(null, '已清空');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 搜索历史列表
     *
     * GET /api/v1/user/search-history
     */
    public function searchHistory(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $limit = min(20, max(1, (int) $request->get('limit', 10)));

        $list = $this->itemService->getSearchHistory($userId, $limit);
        return $this->success($list);
    }

    /**
     * 清空搜索历史
     *
     * POST /api/v1/user/search-history-clear
     */
    public function searchHistoryClear(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        try {
            $this->itemService->clearSearchHistory($userId);
            return $this->success(null, '已清空');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除单条搜索历史
     *
     * POST /api/v1/user/search-history-remove  id=1
     */
    public function searchHistoryRemove(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $id = $request->post('id', '');
        if (empty($id)) {
            return $this->error('记录ID不能为空');
        }

        try {
            $this->itemService->removeSearchHistory($id, $userId);
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}