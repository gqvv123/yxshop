<?php

namespace app\service;

use app\repository\CartRepository;
use app\repository\UserCouponRepository;
use app\model\Cart;
use app\model\UserCoupon;
use app\exception\BusinessException;
use Exception;

/**
 * 购物车服务
 *
 * 4 层架构：所有数据访问通过 CartRepository 意图揭示方法。
 */
class CartService extends BaseService
{
    protected ItemService $itemService;
    protected UserCouponRepository $userCouponRepository;

    public function __construct(?CartRepository $repository = null)
    {
        parent::__construct($repository ?? new CartRepository());
        $this->itemService = new ItemService();
        $this->userCouponRepository = new UserCouponRepository();
    }

    /**
     * 获取用户购物车（含汇总金额/数量/选中金额/店铺分组）
     */
    public function getCartList($userId, $appId)
    {
        try {
            $cartItems = $this->repository->getUserCart($userId, $appId);

            $totalAmount = 0;
            $totalQuantity = 0;
            $selectedAmount = 0;
            $selectedQuantity = 0;
            $storeGroups = [];

            foreach ($cartItems as $item) {
                $item->total_price = $item->price * $item->quantity;
                $totalAmount += $item->total_price;
                $totalQuantity += $item->quantity;

                $isSelected = (int) ($item->is_selected ?? 1) === 1;
                if ($isSelected) {
                    $selectedAmount += $item->total_price;
                    $selectedQuantity += $item->quantity;
                }

                // 店铺分组
                $storeId = (int) ($item->store_id ?? 0);
                if (!isset($storeGroups[$storeId])) {
                    $storeGroups[$storeId] = [
                        'store_id'   => $storeId,
                        'store_name' => $storeId > 0 ? '店铺' . $storeId : '自营',
                        'items'      => [],
                        'store_amount' => 0,
                        'store_count'  => 0,
                    ];
                }
                $storeGroups[$storeId]['items'][] = $item;
                $storeGroups[$storeId]['store_amount'] += $item->total_price;
                $storeGroups[$storeId]['store_count'] += 1;
            }

            return [
                'items'             => $cartItems,
                'stores'            => array_values($storeGroups),
                'total_amount'      => $totalAmount,
                'total_quantity'    => $totalQuantity,
                'selected_amount'   => $selectedAmount,
                'selected_quantity' => $selectedQuantity,
                'all_selected'      => $selectedQuantity === $totalQuantity && $totalQuantity > 0,
            ];
        } catch (Exception $e) {
            $this->logError('获取购物车列表失败',
                compact('userId', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 加入购物车（同商品同规格累加数量）
     */
    public function addToCart(array $data)
    {
        try {
            $this->logInfo('添加商品到购物车开始', ['data' => $data]);

            $item = $this->itemService->findOrFail($data['item_id']);
            if (!$item->is_on_sale) {
                throw new BusinessException('商品已下架');
            }

            // 防御性强转：确保 quantity 为正整数
            $quantity = (int) ($data['quantity'] ?? 1);
            if ($quantity <= 0) {
                $quantity = 1;
            }

            if (!$item->hasStock($quantity)) {
                throw new BusinessException('库存不足');
            }

            $existing = $this->repository->getCartItem(
                $data['user_id'],
                $data['item_id'],
                $data['spec_key'] ?? ''
            );

            if ($existing) {
                $existing->quantity += $quantity;
                $existing->save();
                $cartItem = $existing;
            } else {
                $now = time();
                $cartItem = $this->repository->create([
                    'user_id'       => $data['user_id'],
                    'item_id'       => $data['item_id'],
                    // 字段对齐 Item 模型实际字段（name，不是 goods_name）
                    'item_name'     => $item->name,
                    'item_image'    => $item->getMainImageAttribute() ?: '',
                    'spec_key'      => $data['spec_key'] ?? '',
                    'spec_key_name' => $data['spec_key_name'] ?? '',
                    'price'         => $item->getPrice($data['spec_id'] ?? 0),
                    'quantity'      => $quantity,
                    'app_id'        => $data['app_id'],
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ]);
            }

            $this->logInfo('添加商品到购物车成功', ['cart_id' => $cartItem->id]);
            return $cartItem;
        } catch (Exception $e) {
            $this->logError('添加商品到购物车失败', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 更新购物车商品数量（quantity=0 视为删除）
     */
    public function updateCartItem($cartId, $userId, $quantity)
    {
        try {
            $cartItem = $this->repository->findUserCartItem((int) $cartId, (int) $userId);
            if (!$cartItem) {
                throw new BusinessException('购物车记录不存在', 404);
            }

            if ((int) $quantity === 0) {
                $cartItem->delete();
                return null;
            }

            $item = $this->itemService->findOrFail($cartItem->item_id);
            if (!$item->hasStock($quantity)) {
                throw new BusinessException('库存不足');
            }

            $cartItem->quantity = (int) $quantity;
            $cartItem->save();
            return $cartItem;
        } catch (Exception $e) {
            $this->logError('更新购物车商品数量失败',
                compact('cartId', 'userId', 'quantity') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 删除购物车单条
     */
    public function removeCartItem($cartId, $userId)
    {
        try {
            $cartItem = $this->repository->findUserCartItem((int) $cartId, (int) $userId);
            if (!$cartItem) {
                throw new BusinessException('购物车记录不存在', 404);
            }
            $cartItem->delete();
            return true;
        } catch (Exception $e) {
            $this->logError('删除购物车商品失败',
                compact('cartId', 'userId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 清空购物车
     */
    public function clearCart($userId, $appId)
    {
        try {
            $this->repository->clearUserCart($userId, $appId);
            return true;
        } catch (Exception $e) {
            $this->logError('清空购物车失败',
                compact('userId', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 购物车统计
     */
    public function getCartStats($userId, $appId)
    {
        try {
            return $this->repository->getCartStats($userId, $appId);
        } catch (Exception $e) {
            $this->logError('获取购物车统计失败',
                compact('userId', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 获取购物车数量徽标（轻量接口，仅返回总件数）
     */
    public function getCartCount($userId, $appId)
    {
        try {
            $stats = $this->repository->getCartStats($userId, $appId);
            return [
                'count'         => (int) $stats['total_quantity'],
                'item_count'    => (int) $stats['item_count'],
            ];
        } catch (Exception $e) {
            $this->logError('获取购物车数量失败',
                compact('userId', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 单个/批量选中切换
     *
     * @param int $userId
     * @param array $cartIds  购物车ID列表
     * @param int $isSelected 0取消选中 1选中
     * @return int 受影响行数
     */
    public function toggleSelect($userId, array $cartIds, $isSelected)
    {
        try {
            $affected = $this->repository->toggleSelect($userId, $cartIds, (int) $isSelected);

            $this->logInfo('购物车选中切换', [
                'user_id' => $userId,
                'cart_ids' => $cartIds,
                'is_selected' => $isSelected,
                'affected' => $affected,
            ]);
            return $affected;
        } catch (Exception $e) {
            $this->logError('购物车选中切换失败',
                compact('userId', 'cartIds', 'isSelected') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 全选/取消全选
     *
     * @param int $userId
     * @param int $appId
     * @param int $isSelected 0取消全选 1全选
     * @return int 受影响行数
     */
    public function toggleSelectAll($userId, $appId, $isSelected)
    {
        try {
            $affected = $this->repository->toggleSelectAll($userId, $appId, (int) $isSelected);

            $this->logInfo('购物车全选切换', [
                'user_id' => $userId,
                'app_id' => $appId,
                'is_selected' => $isSelected,
                'affected' => $affected,
            ]);
            return $affected;
        } catch (Exception $e) {
            $this->logError('购物车全选切换失败',
                compact('userId', 'appId', 'isSelected') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 购物车结算预览（选中商品总价、可用优惠券、运费预估）
     *
     * @param int $userId
     * @param int $appId
     * @return array
     */
    public function checkoutPreview($userId, $appId)
    {
        try {
            $cartItems = $this->repository->getUserCart($userId, $appId);

            $selectedItems = [];
            $totalAmount = 0;
            $totalQuantity = 0;

            foreach ($cartItems as $item) {
                if ((int) ($item->is_selected ?? 1) === 1) {
                    $item->total_price = $item->price * $item->quantity;
                    $selectedItems[] = $item;
                    $totalAmount += $item->total_price;
                    $totalQuantity += $item->quantity;
                }
            }

            // 查可用优惠券（满减券）
            $availableCoupons = $this->userCouponRepository->getUnusedWithCouponByUser($userId)
                ->filter(function ($uc) use ($totalAmount) {
                    $coupon = $uc->coupon;
                    if (!$coupon || $coupon->status != 1) return false;
                    $minAmount = (float) ($coupon->min_amount ?? 0);
                    return $totalAmount >= $minAmount;
                })
                ->map(function ($uc) use ($totalAmount) {
                    $coupon = $uc->coupon;
                    $actualDiscount = $coupon->calculateDiscount($totalAmount);
                    if ($actualDiscount <= 0) {
                        return null;
                    }

                    return [
                        'user_coupon_id' => $uc->id,
                        'coupon_id'      => $coupon->id,
                        'name'           => $coupon->name,
                        'type'           => $coupon->type ?? 1,
                        'discount_amount'=> $actualDiscount,
                        'min_amount'     => (float) $coupon->min_amount,
                        'after_discount' => round(max(0, $totalAmount - $actualDiscount), 2),
                        'end_at'         => $coupon->end_at ?? null,
                    ];
                })
                ->filter()   // 过滤 null（优惠金额为0的券）
                ->values()
                ->toArray();

            // 运费预估（简化：免邮商品为0，非免邮默认10元，实际应走运费模板）
            $shippingFee = 0;
            foreach ($selectedItems as $item) {
                $realItem = $this->itemService->find($item->item_id);
                if ($realItem && !$realItem->isFreeShipping()) {
                    $shippingFee = 10;
                    break;
                }
            }

            return [
                'selected_items'      => $selectedItems,
                'selected_count'      => count($selectedItems),
                'selected_quantity'   => $totalQuantity,
                'goods_amount'        => round($totalAmount, 2),
                'shipping_fee'        => $shippingFee,
                'available_coupons'   => $availableCoupons,
                'best_coupon'         => !empty($availableCoupons) ? $availableCoupons[0] : null,
                'estimated_pay'       => round($totalAmount + $shippingFee - ($availableCoupons[0]['discount_amount'] ?? 0), 2),
            ];
        } catch (Exception $e) {
            $this->logError('购物车结算预览失败',
                compact('userId', 'appId') + ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
