<?php

namespace app\repository;

use app\model\Cart;

/**
 * 购物车仓储类
 */
class CartRepository extends BaseRepository
{
    protected $model = Cart::class;

    /**
     * 获取用户购物车
     */
    public function getUserCart($userId, $appId = 0)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->with(['item.images']);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * 获取购物车商品（用于查重：相同 user + item + spec 视为同一条）
     */
    public function getCartItem($userId, $itemId, $specKey = '')
    {
        return $this->query()
            ->where('user_id', $userId)
            ->where('item_id', $itemId)
            ->where('spec_key', $specKey)
            ->first();
    }

    /**
     * 按 (cart_id, user_id) 查购物车条目，用于更新/删除时的归属校验
     */
    public function findUserCartItem(int $cartId, int $userId)
    {
        return $this->query()
            ->where('id', $cartId)
            ->where('user_id', $userId)
            ->first();
    }

    /**
     * 获取购物车统计
     */
    public function getCartStats($userId, $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        $items = $query->get();

        $totalAmount = 0;
        $totalQuantity = 0;

        foreach ($items as $item) {
            $totalAmount += $item->price * $item->quantity;
            $totalQuantity += $item->quantity;
        }

        return [
            'item_count' => $items->count(),
            'total_quantity' => $totalQuantity,
            'total_amount' => $totalAmount,
        ];
    }

    /**
     * 清空用户购物车
     */
    public function clearUserCart($userId, $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->delete();
    }

    /**
     * 删除过期购物车商品
     */
    public function deleteExpiredItems($days = 30)
    {
        $expiredTime = time() - ($days * 24 * 3600);
        
        return $this->query()
            ->where('created_at', '<', $expiredTime)
            ->delete();
    }

    /**
     * 获取购物车商品ID列表
     */
    public function getCartItemIds($userId, $appId = 0)
    {
        $query = $this->query()->where('user_id', $userId);

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }

        return $query->pluck('item_id')->toArray();
    }

    /**
     * 单个/批量选中切换
     */
    public function toggleSelect(int $userId, array $cartIds, int $isSelected): int
    {
        return $this->query()
            ->where('user_id', $userId)
            ->whereIn('id', $cartIds)
            ->update(['is_selected' => $isSelected, 'updated_at' => time()]);
    }

    /**
     * 全选/取消全选
     */
    public function toggleSelectAll(int $userId, int $appId, int $isSelected): int
    {
        $query = $this->query()->where('user_id', $userId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->update(['is_selected' => $isSelected, 'updated_at' => time()]);
    }
}
