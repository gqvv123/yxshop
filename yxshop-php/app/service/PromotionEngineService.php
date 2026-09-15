<?php

namespace app\service;

use app\model\Promotion;
use app\model\PromItem;
use app\model\PromOrder;
use app\model\Item;
use app\repository\PromItemRepository;
use app\repository\PromOrderRepository;
use app\exception\BusinessException;
use Illuminate\Support\Collection;

class PromotionEngineService
{
    const PRIORITY_SECKILL = 100;
    const PRIORITY_GROUP_BUY = 90;
    const PRIORITY_ITEM_PROM = 70;
    const PRIORITY_ORDER_PROM = 50;

    protected PromItemRepository $promItemRepo;
    protected $promOrderRepo;

    public function __construct()
    {
        $this->promItemRepo = new PromItemRepository();
        $this->promOrderRepo = new PromOrderRepository();
    }

    public function calculatePromotion(array $orderItems, int $userId = 0, int $appId = 0): array
    {
        $result = [
            'discount_amount' => 0,
            'discount_details' => [],
            'gift_items' => [],
        ];

        $itemPromotions = $this->resolveItemPromotions($orderItems, $appId);
        $orderPromotions = $this->resolveOrderPromotions($orderItems, $appId);

        foreach ($orderItems as $orderItem) {
            $itemId = $orderItem['item_id'] ?? 0;
            $quantity = $orderItem['quantity'] ?? 1;
            $unitPrice = (float) ($orderItem['unit_price'] ?? 0);
            $lineTotal = $unitPrice * $quantity;

            if (isset($itemPromotions[$itemId])) {
                $promo = $itemPromotions[$itemId];
                $discount = $this->calculateItemDiscount($promo, $unitPrice, $quantity);
                $result['discount_amount'] += $discount;
                $result['discount_details'][] = [
                    'item_id' => $itemId,
                    'promotion_type' => $promo['type'],
                    'promotion_id' => $promo['id'],
                    'original_price' => $lineTotal,
                    'discount_amount' => $discount,
                    'final_price' => max($lineTotal - $discount, 0.01),
                ];
            }
        }

        $subtotal = 0;
        foreach ($orderItems as $orderItem) {
            $subtotal += (float) ($orderItem['unit_price'] ?? 0) * ($orderItem['quantity'] ?? 1);
        }
        $subtotalAfterItemDiscount = $subtotal - $result['discount_amount'];

        foreach ($orderPromotions as $orderPromo) {
            $discount = $this->calculateOrderDiscount($orderPromo, $subtotalAfterItemDiscount);
            if ($discount > 0) {
                $result['discount_amount'] += $discount;
                $result['discount_details'][] = [
                    'promotion_type' => 'order_prom',
                    'promotion_id' => $orderPromo['id'],
                    'threshold' => $orderPromo['threshold'] ?? 0,
                    'discount_amount' => $discount,
                ];
            }
        }

        $giftItems = $this->resolveGiftItems($orderItems, $appId);
        $result['gift_items'] = $giftItems;

        return $result;
    }

    protected function resolveItemPromotions(array $orderItems, int $appId): array
    {
        $promotions = [];
        $now = time();
        $itemIds = array_column($orderItems, 'item_id');
        $itemIds = array_filter(array_map('intval', $itemIds));

        if (empty($itemIds)) {
            return $promotions;
        }

        // 秒杀商品（商业版功能，开源版跳过）
        $seckillItems = collect();
        if (class_exists('\\commercial\\seckill_pro\\Model\\SeckillItem') &&
            class_exists('\\commercial\\seckill_pro\\Repository\\SeckillItemRepository')) {
            $seckillRepo = new \commercial\seckill_pro\Repository\SeckillItemRepository();
            $seckillItems = $seckillRepo->query()->whereIn('item_id', $itemIds)
                ->where('app_id', $appId)
                ->whereHas('session', function ($q) use ($now) {
                    $q->where('status', 1)
                      ->where('start_at', '<=', $now)
                      ->where('end_at', '>', $now);
                })
                ->get()
                ->keyBy('item_id');
        }

        // 拼团商品（商业版功能，开源版跳过）
        $groupBuys = collect();
        if (class_exists('\\commercial\\group_buy\\Model\\GroupBuy') &&
            class_exists('\\commercial\\group_buy\\Repository\\GroupBuyRepository')) {
            $groupBuyRepo = new \commercial\group_buy\Repository\GroupBuyRepository();
            $groupBuys = $groupBuyRepo->query()->whereIn('item_id', $itemIds)
                ->where('app_id', $appId)
                ->where('status', \commercial\group_buy\Model\GroupBuy::STATUS_ENABLED)
                ->where('start_at', '<=', $now)
                ->where('end_at', '>', $now)
                ->get()
                ->keyBy('item_id');
        }

        $promItems = $this->promItemRepo->query()->whereIn('item_id', $itemIds)
            ->where('app_id', $appId)
            ->whereHas('promotion', function ($q) use ($now) {
                $q->where('status', 1)
                  ->where('start_time', '<=', $now)
                  ->where('end_time', '>', $now);
            })
            ->with('promotion')
            ->get()
            ->keyBy('item_id');

        foreach ($orderItems as $orderItem) {
            $itemId = $orderItem['item_id'] ?? 0;
            $bestPromo = null;
            $bestPriority = 0;

            if (isset($seckillItems[$itemId])) {
                $seckillItem = $seckillItems[$itemId];
                $bestPromo = [
                    'type' => 'seckill',
                    'id' => $seckillItem->id,
                    'price' => (float) $seckillItem->seckill_price,
                    'priority' => self::PRIORITY_SECKILL,
                ];
                $bestPriority = self::PRIORITY_SECKILL;
            }

            if (isset($groupBuys[$itemId]) && self::PRIORITY_GROUP_BUY > $bestPriority) {
                $groupBuy = $groupBuys[$itemId];
                $bestPromo = [
                    'type' => 'group_buy',
                    'id' => $groupBuy->id,
                    'price' => (float) $groupBuy->group_price,
                    'priority' => self::PRIORITY_GROUP_BUY,
                ];
                $bestPriority = self::PRIORITY_GROUP_BUY;
            }

            if (isset($promItems[$itemId]) && self::PRIORITY_ITEM_PROM > $bestPriority) {
                $promItem = $promItems[$itemId];
                $promotion = $promItem->promotion;
                $bestPromo = [
                    'type' => 'item_prom',
                    'id' => $promItem->id,
                    'discount_type' => $promotion->rule['discount_type'] ?? 'percent',
                    'discount_value' => (float) ($promotion->rule['discount_value'] ?? 0),
                    'original_price' => (float) ($orderItem['unit_price'] ?? 0),
                    'priority' => self::PRIORITY_ITEM_PROM,
                ];
                $bestPriority = self::PRIORITY_ITEM_PROM;
            }

            if ($bestPromo) {
                $promotions[$itemId] = $bestPromo;
            }
        }

        return $promotions;
    }

    protected function resolveOrderPromotions(array $orderItems, int $appId): array
    {
        $now = time();
        $promotions = [];

        $promOrderRepo = new PromOrderRepository();
        $promOrders = $promOrderRepo->query()
            ->where('app_id', $appId)
            ->whereHas('promotion', function ($q) use ($now) {
                $q->where('status', 1)
                  ->where('start_time', '<=', $now)
                  ->where('end_time', '>', $now);
            })
            ->get();

        foreach ($promOrders as $promOrder) {
            $promotion = $promOrder->promotion;
            $promotions[] = [
                'id' => $promOrder->id,
                'threshold' => (float) ($promotion->rule['threshold'] ?? 0),
                'discount_type' => $promotion->rule['discount_type'] ?? 'fixed',
                'discount_value' => (float) ($promotion->rule['discount_value'] ?? 0),
            ];
        }

        return $promotions;
    }

    protected function calculateItemDiscount(array $promo, float $unitPrice, int $quantity): float
    {
        if (isset($promo['price'])) {
            $discount = ($unitPrice - $promo['price']) * $quantity;
            return max($discount, 0);
        }

        if (isset($promo['discount_type']) && $promo['discount_type'] === 'percent') {
            $discount = $unitPrice * $quantity * ($promo['discount_value'] / 100);
            return max($discount, 0);
        }

        if (isset($promo['discount_type']) && $promo['discount_type'] === 'fixed') {
            return min($promo['discount_value'] * $quantity, $unitPrice * $quantity - 0.01);
        }

        return 0;
    }

    protected function calculateOrderDiscount(array $promo, float $subtotal): float
    {
        $threshold = $promo['threshold'] ?? 0;

        if ($threshold > 0 && $subtotal >= $threshold) {
            if ($promo['discount_type'] === 'fixed') {
                return min($promo['discount_value'], $subtotal - 0.01);
            }
            if ($promo['discount_type'] === 'percent') {
                $discount = $subtotal * ($promo['discount_value'] / 100);
                return min($discount, $subtotal - 0.01);
            }
        }

        return 0;
    }

    protected function resolveGiftItems(array $orderItems, int $appId): array
    {
        $now = time();
        $giftItems = [];

        // 赠品活动（商业版功能，开源版跳过）
        $giftActivities = collect();
        if (class_exists('\\commercial\\gift_activity\\Repository\\GiftActivityRepository')) {
            $giftRepo = new \commercial\gift_activity\Repository\GiftActivityRepository();
            $giftActivities = $giftRepo->query()
                ->where('app_id', $appId)
                ->where('status', 1)
                ->where('start_time', '<=', $now)
                ->where('end_time', '>', $now)
                ->get();
        }

        foreach ($giftActivities as $activity) {
            $threshold = (float) ($activity->threshold_amount ?? 0);
            $subtotal = 0;
            foreach ($orderItems as $orderItem) {
                $subtotal += (float) ($orderItem['unit_price'] ?? 0) * ($orderItem['quantity'] ?? 1);
            }

            if ($threshold > 0 && $subtotal >= $threshold) {
                $giftItemIds = $activity->items()->pluck('item_id')->toArray();
                if (!empty($giftItemIds)) {
                    $giftItems[] = [
                        'gift_activity_id' => $activity->id,
                        'item_ids' => $giftItemIds,
                    ];
                }
            }
        }

        return $giftItems;
    }
}