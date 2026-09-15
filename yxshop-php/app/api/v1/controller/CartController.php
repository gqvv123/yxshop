<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\CartService;

class CartController extends BaseController
{
    protected $cartService;
    
    public function __construct()
    {
        $this->cartService = new CartService();
    }
    
    /**
     * 获取购物车列表
     */
    public function getList(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $appId = (int) $request->get('app_id', 0);
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        $result = $this->cartService->getCartList($userId, $appId);
        
        return $this->success($result);
    }
    
    /**
     * 添加商品到购物车
     */
    public function add(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $data = $request->post();

        if (!$userId) {
            return $this->error('用户未登录');
        }

        if (empty($data['item_id'])) {
            return $this->error('商品ID不能为空');
        }

        // 显式取 quantity 并强转为 int，防止 JSON 解析丢字段导致 null
        $quantity = (int) ($request->post('quantity', 1) ?? 1);
        if ($quantity <= 0) {
            return $this->error('商品数量必须大于0');
        }
        $data['quantity'] = $quantity;

        $data['user_id'] = $userId;
        $data['app_id'] = (int) ($data['app_id'] ?? 0);
        
        try {
            $result = $this->cartService->addToCart($data);
            return $this->success($result, '添加成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * 更新购物车商品数量
     */
    public function update(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $cartId = $request->post('cart_id');
        $quantity = $request->post('quantity');
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        if (!$cartId) {
            return $this->error('购物车ID不能为空');
        }
        
        if ($quantity < 0) {
            return $this->error('商品数量不能小于0');
        }
        
        try {
            $result = $this->cartService->updateCartItem($cartId, $userId, $quantity);
            return $this->success($result, '更新成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * 删除购物车商品
     */
    public function remove(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $cartId = $request->post('cart_id');
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        if (!$cartId) {
            return $this->error('购物车ID不能为空');
        }
        
        try {
            $this->cartService->removeCartItem($cartId, $userId);
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * 清空购物车
     */
    public function clear(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $appId = (int) $request->post('app_id', 0);
        
        if (!$userId) {
            return $this->error('用户未登录');
        }
        
        try {
            $this->cartService->clearCart($userId, $appId);
            return $this->success(null, '清空成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
    
    /**
     * 获取购物车数量徽标（轻量接口，用于 tabbar 红点）
     */
    public function getCount(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $appId = (int) $request->get('app_id', 0);

        if (!$userId) {
            return $this->error('用户未登录');
        }

        $result = $this->cartService->getCartCount($userId, $appId);
        return $this->success($result);
    }

    /**
     * 选中/取消选中购物车商品
     *
     * POST cart_ids: [1,2,3]  is_selected: 0|1
     */
    public function select(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $cartIds = $request->post('cart_ids', []);
        $isSelected = (int) $request->post('is_selected', 1);

        if (empty($cartIds) || !is_array($cartIds)) {
            return $this->error('请选择要操作的商品');
        }

        try {
            $affected = $this->cartService->toggleSelect($userId, $cartIds, $isSelected);
            return $this->success(['affected' => $affected], '操作成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 全选/取消全选
     *
     * POST is_selected: 0|1
     */
    public function selectAll(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $appId = (int) $request->post('app_id', 0);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $isSelected = (int) $request->post('is_selected', 1);

        try {
            $affected = $this->cartService->toggleSelectAll($userId, $appId, $isSelected);
            return $this->success(['affected' => $affected], '操作成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 购物车结算预览
     *
     * 返回选中商品总价、可用优惠券、运费预估
     */
    public function checkoutPreview(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $appId = (int) $request->get('app_id', 0);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        try {
            $result = $this->cartService->checkoutPreview($userId, $appId);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}