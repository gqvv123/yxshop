<?php

namespace app\service;
use app\model\UserCoupon;
use app\exception\BusinessException;
use Exception;

/**
 * OrderAmountTrait：从 OrderService.php 抽离的preloadItems、checkStock、calculateOrderAmount、processCoupon、calculateMemberDiscount、calculateShippingFee、createOrderItems、createOrderAddress、reduceStock等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\service
 */
trait OrderAmountTrait
{
    private function preloadItems(array $items)
    {
        $itemIds = array_column($items, 'item_id');
        $itemIds = array_map('intval', array_unique($itemIds));
        return $this->itemService->repository->query()->whereIn('id', $itemIds)->get()->keyBy('id');
    }

    private function checkStock(array $items, $itemModels): void
    {
        foreach ($items as $item) {
            $goods = $itemModels[$item['item_id']] ?? null;
            if (!$goods) {
                throw new BusinessException("商品不存在: {$item['item_id']}");
            }
            if (!$goods->hasStock($item['quantity'])) {
                throw new BusinessException("商品 {$goods->name} 库存不足");
            }
        }
    }

    private function calculateOrderAmount(array $items, $itemModels)
    {
        $total = 0;
        foreach ($items as $item) {
            $goods = $itemModels[$item['item_id']] ?? null;
            if (!$goods) {
                throw new BusinessException("商品不存在: {$item['item_id']}");
            }
            $total += $goods->getPrice($item['spec_id'] ?? 0) * $item['quantity'];
        }
        return $total;
    }

    private function processCoupon($userId, $couponId, $orderAmount)
    {
        $userCoupon = $this->userCouponRepository->findByUserAndCoupon(
            (int) $userId, (int) $couponId, UserCoupon::STATUS_UNUSED
        );
        if (!$userCoupon) {
            throw new BusinessException('优惠券不存在或已使用');
        }
        if (!$userCoupon->canUse()) {
            throw new BusinessException('优惠券不可用');
        }
        $discount = $userCoupon->coupon->calculateDiscount($orderAmount);
        if ($discount <= 0) {
            throw new BusinessException('优惠券不满足使用条件');
        }
        return $discount;
    }

    /**
     * 核销订单使用的优惠券
     *
     * 下单成功即把券置为已使用并挂上订单号；订单取消/退款时由 restoreCoupon 退回。
     * 历史缺陷：createOrder 只算了 coupon_price 却从不核销，同一张券可反复下单。
     *
     * @param int $userId
     * @param int $couponId 券模板 ID（与 processCoupon 口径一致）
     * @param int $orderId
     * @return void
     */
    private function useCouponForOrder($userId, $couponId, $orderId): void
    {
        if (!$couponId) {
            return;
        }
        $userCoupon = $this->userCouponRepository->findByUserAndCoupon(
            (int) $userId,
            (int) $couponId,
            UserCoupon::STATUS_UNUSED
        );
        if (!$userCoupon) {
            // 走到这里说明并发下已被别的订单抢用，必须中断本单，避免重复抵扣
            throw new BusinessException('优惠券已被使用，请重新选择');
        }
        $userCoupon->status = UserCoupon::STATUS_USED;
        $userCoupon->order_id = (int) $orderId;
        $userCoupon->use_time = time();
        $userCoupon->save();
    }

    /**
     * 结算试算（只算不落库）
     *
     * 与 createOrder 共用同一套金额算法，保证「结算页看到的实付」和「下单后的实付」一致。
     * 支持购物车结算与「立即购买」两种来源：前端直接传 items，服务端不依赖购物车选中态。
     *
     * 入参：
     *   user_id     必填
     *   items       必填 [{item_id, quantity, spec_id?}]
     *   address_id? 可选（用于按地区匹配运费，缺省按商品自身配送模板）
     *   delivery_id?可选
     *   coupon_id?  可选（券模板 id，不是用户券 id）
     *
     * @param array $data
     * @return array
     */
    public function previewOrder(array $data): array
    {
        $userId = (int) ($data['user_id'] ?? 0);
        if ($userId <= 0) {
            throw new BusinessException('用户未登录');
        }

        $items = $this->normalizePreviewItems($data['items'] ?? []);
        if (!$items) {
            throw new BusinessException('订单商品不能为空');
        }

        $user = $this->userService->findOrFail($userId);
        $itemModels = $this->preloadItems($items);
        $orderAmount = $this->calculateOrderAmount($items, $itemModels);

        // 优惠券：不满足条件时不让整页报错，而是把原因回传前端
        $couponId = (int) ($data['coupon_id'] ?? 0);
        $couponDiscount = 0.0;
        $couponError = '';
        if ($couponId > 0) {
            try {
                $couponDiscount = (float) $this->processCoupon($userId, $couponId, $orderAmount);
            } catch (Exception $e) {
                $couponError = $e->getMessage();
                $couponId = 0;
            }
        }

        $memberDiscount = (float) $this->calculateMemberDiscount($user, $orderAmount);
        $shippingFee = (float) $this->calculateShippingFee(
            $items,
            (int) ($data['address_id'] ?? 0),
            $itemModels,
            (float) $orderAmount,
            (int) ($data['delivery_id'] ?? 0)
        );
        $payAmount = max(0, round($orderAmount + $shippingFee - $couponDiscount - $memberDiscount, 2));

        return [
            'goods'            => $this->buildPreviewGoods($items, $itemModels),
            'goods_amount'     => round($orderAmount, 2),
            'member_discount'  => round($memberDiscount, 2),
            'coupon_discount'  => round($couponDiscount, 2),
            'coupon_error'     => $couponError,
            'shipping_fee'     => round($shippingFee, 2),
            'pay_amount'       => $payAmount,
            'total_quantity'   => array_sum(array_column($items, 'quantity')),
            'member'           => $this->buildMemberBrief($user),
            'available_coupons'=> $this->listUsableCoupons($userId, (float) $orderAmount),
            'selected_coupon_id' => $couponId,
        ];
    }

    /**
     * 归一化试算入参：丢掉无效条目、数量至少 1
     *
     * @param array $items
     * @return array
     */
    private function normalizePreviewItems(array $items): array
    {
        $normalized = [];
        foreach ($items as $it) {
            $itemId = (int) ($it['item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $normalized[] = [
                'item_id'  => $itemId,
                'quantity' => max(1, (int) ($it['quantity'] ?? 1)),
                'spec_id'  => (int) ($it['spec_id'] ?? 0),
            ];
        }
        return $normalized;
    }

    /**
     * 试算商品明细（仅展示用，价格真源仍是服务端）
     *
     * @param array $items
     * @param mixed $itemModels
     * @return array
     */
    private function buildPreviewGoods(array $items, $itemModels): array
    {
        $goods = [];
        foreach ($items as $it) {
            $model = $itemModels[$it['item_id']] ?? null;
            if (!$model) {
                continue;
            }
            $price = (float) $model->getPrice($it['spec_id']);
            $goods[] = [
                'item_id'   => (int) $model->id,
                'name'      => (string) $model->name,
                'cover'     => (string) ($model->cover ?? ''),
                'price'     => $price,
                'quantity'  => (int) $it['quantity'],
                'line_total'=> round($price * $it['quantity'], 2),
                'spec_id'   => (int) $it['spec_id'],
            ];
        }
        return $goods;
    }

    /**
     * 会员等级摘要（前端展示「黄金会员 9 折」用）
     *
     * @param mixed $user
     * @return array
     */
    private function buildMemberBrief($user): array
    {
        $rate = (float) ($user->discount ?? 10);
        return [
            'level_id'   => (int) ($user->level_id ?? 0),
            'level_name' => (string) ($user->level_name ?? '普通会员'),
            'discount'   => $rate,
            'has_discount' => $rate > 0 && $rate < 10,
        ];
    }

    /**
     * 当前订单金额下可用的优惠券
     *
     * 口径与 CartService::checkoutPreview 保持一致：未使用 + 券有效 + 满足门槛 + 折扣 > 0。
     *
     * @param int $userId
     * @param float $orderAmount
     * @return array
     */
    private function listUsableCoupons(int $userId, float $orderAmount): array
    {
        try {
            return $this->userCouponRepository->getUnusedWithCouponByUser($userId)
                ->filter(function ($uc) use ($orderAmount) {
                    $coupon = $uc->coupon;
                    if (!$coupon || (int) $coupon->status !== 1) {
                        return false;
                    }
                    return $orderAmount >= (float) ($coupon->min_amount ?? 0);
                })
                ->map(function ($uc) use ($orderAmount) {
                    $coupon = $uc->coupon;
                    $discount = (float) $coupon->calculateDiscount($orderAmount);
                    if ($discount <= 0) {
                        return null;
                    }
                    return [
                        'coupon_id'       => (int) $coupon->id,
                        'user_coupon_id'  => (int) $uc->id,
                        'name'            => (string) $coupon->name,
                        'type'            => (int) ($coupon->type ?? 1),
                        'discount_amount' => round($discount, 2),
                        'min_amount'      => (float) ($coupon->min_amount ?? 0),
                        'end_at'          => $coupon->end_at ?? null,
                    ];
                })
                ->filter()
                ->values()
                ->toArray();
        } catch (Exception $e) {
            // 券数据异常不影响试算主流程
            return [];
        }
    }

    /**
     * 会员等级折扣金额
     *
     * 等级 discount（或旧字段 agio）以「折」为单位：10 = 原价，9.5 = 9.5 折。
     * 只对商品金额生效，运费不参与折扣。
     */
    private function calculateMemberDiscount($user, float $orderAmount): float
    {
        if ($orderAmount <= 0 || !$user) {
            return 0.0;
        }

        $rate = (float) ($user->discount ?? 10);
        if ($rate <= 0 || $rate >= 10) {
            return 0.0;
        }

        $payable = round($orderAmount * $rate / 10, 2);
        return max(0, round($orderAmount - $payable, 2));
    }

    /**
     * 计算运费。
     *
     * 优先按用户在下单时选择的配送模板（delivery_id）匹配计费规则；
     * 未指定时退回到商品自身的配送模板；
     * 两者都没有可用规则时按包邮处理（0 元）。
     *
     * @param array $items     订单商品
     * @param int   $addressId 收货地址 ID
     * @param int   $deliveryId 用户选择的配送模板 ID
     */
    private function calculateShippingFee(
        array $items,
        $addressId,
        $itemModels,
        float $orderAmount,
        int $deliveryId = 0
    ): float {
        $ruleService = new DeliveryRuleService();

        // 收货地区：用于匹配「指定地区」计费规则
        $regionId = 0;
        try {
            $address = $this->userAddressRepository->findOrFail((int) $addressId);
            $regionId = (int) ($address->district_id ?: $address->city_id ?: $address->province_id);
        } catch (Exception $e) {
            // 地址取不到时按全国规则计算
            $regionId = 0;
        }

        $quantity = 0;
        foreach ($items as $item) {
            $quantity += (int) ($item['quantity'] ?? 0);
        }
        $quantity = max(1, $quantity);

        // 1) 用户显式选择的配送模板
        if ($deliveryId > 0) {
            $result = $ruleService->calcFreight($deliveryId, $quantity, $orderAmount, $regionId);
            return (float) $result['freight'];
        }

        // 2) 商品自身的配送模板（取第一个有模板的商品）
        foreach ($itemModels as $goods) {
            $templateId = (int) ($goods->shipping_template_id ?? 0);
            if ($templateId > 0) {
                $result = $ruleService->calcFreight($templateId, $quantity, $orderAmount, $regionId);
                return (float) $result['freight'];
            }
        }

        // 3) 无任何可用配送规则 → 包邮
        return 0.0;
    }

    private function createOrderItems($orderId, array $items, $itemModels): void
    {
        foreach ($items as $item) {
            $goods = $itemModels[$item['item_id']] ?? null;
            if (!$goods) {
                throw new BusinessException("商品不存在: {$item['item_id']}");
            }
            $price = $goods->getPrice($item['spec_id'] ?? 0);
            $quantity = (int) $item['quantity'];

            $this->orderItemRepository->create([
                'order_id'        => $orderId,
                'item_id'         => $item['item_id'],
                'name'            => $goods->name,
                'image'           => $goods->getMainImageAttribute(),
                'item_no'         => $goods->item_no ?? '',
                'spec_type'       => $goods->spec_type ?? 0,
                'spec_sku_id'     => $item['spec_key'] ?? '',
                'item_attr'       => $item['spec_key_name'] ?? '',
                'item_sku_id'     => $item['spec_id'] ?? 0,
                'content'         => $goods->description ?? '',
                'item_price'      => $price,
                'line_price'      => $goods->market_price ?? 0,
                'total_num'       => $quantity,
                'total_price'     => $price * $quantity,
                'total_pay_price' => $price * $quantity,
                'give_integral'   => $goods->give_integral ?? 0,
                'user_id'         => $item['user_id'] ?? 0,
                'app_id'          => $goods->app_id,
                'created_at'      => time(),
                'updated_at'      => time(),
            ]);
        }
    }

    private function createOrderAddress($orderId, $addressId, $appId): void
    {
        $userAddress = $this->userAddressRepository->findOrFail($addressId);

        $this->orderAddressRepository->create([
            'order_id'    => $orderId,
            'name'        => $userAddress->name,
            'phone'       => $userAddress->phone,
            'province_id' => $userAddress->province_id,
            'city_id'     => $userAddress->city_id,
            'region_id'   => $userAddress->district_id,
            // 快照省市县名称，避免 region 基础数据缺失时订单地址只剩门牌号
            'province'    => (string) ($userAddress->province ?? ''),
            'city'        => (string) ($userAddress->city ?? ''),
            'district'    => (string) ($userAddress->district ?? ''),
            'detail'      => $userAddress->detail,
            'postal_code' => $userAddress->zip_code,
            'is_default'  => $userAddress->is_default,
            'user_id'     => $userAddress->user_id,
            'app_id'      => $appId,
            'created_at'  => time(),
            'updated_at'  => time(),
        ]);
    }

    private function reduceStock(array $items, $itemModels): void
    {
        foreach ($items as $item) {
            $goods = $itemModels[$item['item_id']] ?? null;
            if (!$goods) {
                throw new BusinessException("商品不存在: {$item['item_id']}");
            }
            $goods->reduceStock($item['quantity'], $item['spec_id'] ?? 0, '订单出库');
        }
    }
}
