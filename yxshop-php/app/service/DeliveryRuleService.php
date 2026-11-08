<?php

namespace app\service;

use app\model\DeliveryRule;
use app\repository\DeliveryRuleRepository;

/**
 * 配送计费规则服务。
 *
 * 从 C 端 DeliveryController 中抽离，供两处复用：
 *   1. C 端运费试算（/api/v1/delivery/freight）
 *   2. 下单时真实计算运费（OrderService::calculateShippingFee）
 *
 * 计费规则（yxshop_delivery_rules）：
 * - delivery_type 10=按件数 20=按金额 30=两者同时满足
 * - first/first_fee                首件（首额）与其运费
 * - additional/additional_fee      续件（续额）与其运费
 * - min_price/min_num              免邮门槛（金额 / 件数）
 * - region                         适用地区，空或 '0' 表示全国
 */
class DeliveryRuleService
{
    protected DeliveryRuleRepository $ruleRepository;

    public function __construct(?DeliveryRuleRepository $ruleRepository = null)
    {
        $this->ruleRepository = $ruleRepository ?? new DeliveryRuleRepository();
    }

    /**
     * 试算运费
     *
     * @param int    $deliveryId 配送模板 ID
     * @param int    $quantity   商品件数
     * @param float  $amount     商品金额
     * @param int    $regionId   收货地区 ID（用于匹配地区规则）
     * @return array{freight: float, free: bool, rule: ?DeliveryRule, reason: string}
     */
    public function calcFreight(int $deliveryId, int $quantity, float $amount, int $regionId = 0): array
    {
        if ($deliveryId <= 0) {
            return ['freight' => 0.0, 'free' => true, 'rule' => null, 'reason' => '未选择配送方式'];
        }

        $rules = $this->ruleRepository->query()
            ->where('delivery_id', $deliveryId)
            ->get();

        $matched = $this->matchRule($rules, $regionId);
        if (!$matched) {
            return ['freight' => 0.0, 'free' => true, 'rule' => null, 'reason' => '该地区暂无可用配送规则，默认包邮'];
        }

        [$freight, $free, $reason] = $this->calculateByRule($matched, max(1, $quantity), $amount);

        return ['freight' => $freight, 'free' => $free, 'rule' => $matched, 'reason' => $reason];
    }

    /**
     * 按地区挑一条最合适的规则：优先精确匹配，否则取全国默认规则
     *
     * @param iterable $rules
     */
    public function matchRule($rules, int $regionId): ?DeliveryRule
    {
        $exact = null;
        $default = null;

        foreach ($rules as $rule) {
            $region = trim((string) ($rule->region ?? ''));
            if ($region === '' || $region === '0' || $region === '[]') {
                $default = $default ?? $rule;
                continue;
            }
            // region 存的是地区 id 列表（JSON 或逗号分隔）
            $ids = [];
            if (str_starts_with($region, '[')) {
                $decoded = json_decode($region, true);
                $ids = is_array($decoded) ? array_map('intval', $decoded) : [];
            } else {
                $ids = array_map('intval', array_filter(explode(',', $region)));
            }
            if ($regionId && in_array($regionId, $ids, true)) {
                $exact = $exact ?? $rule;
            }
        }

        return $exact ?? $default;
    }

    /**
     * 按规则计算运费
     *
     * @return array{0: float, 1: bool, 2: string} [运费, 是否包邮, 说明]
     */
    public function calculateByRule(DeliveryRule $rule, int $quantity, float $amount): array
    {
        $type = (int) $rule->delivery_type;
        $minPrice = (float) $rule->min_price;
        $minNum = (int) $rule->min_num;

        // 免邮判定
        if ($type === DeliveryRule::DELIVERY_TYPE_AMOUNT && $minPrice > 0 && $amount >= $minPrice) {
            return [0.0, true, '满 ' . $minPrice . ' 元包邮'];
        }
        if ($type === DeliveryRule::DELIVERY_TYPE_QUANTITY && $minNum > 0 && $quantity >= $minNum) {
            return [0.0, true, '满 ' . $minNum . ' 件包邮'];
        }
        if ($type === DeliveryRule::DELIVERY_TYPE_BOTH) {
            $amountFree = $minPrice > 0 && $amount >= $minPrice;
            $numFree = $minNum > 0 && $quantity >= $minNum;
            if ($amountFree || $numFree) {
                return [0.0, true, '满足包邮条件'];
            }
        }

        $first = max(1, (int) $rule->first);
        $firstFee = (float) $rule->first_fee;
        $additional = max(0, (int) $rule->additional);
        $additionalFee = (float) $rule->additional_fee;

        if ($quantity <= $first) {
            return [$firstFee, false, '首件计费'];
        }

        $rest = $quantity - $first;
        $restUnits = $additional > 0 ? (int) ceil($rest / $additional) : $rest;
        $freight = round($firstFee + $restUnits * $additionalFee, 2);

        return [$freight, false, '首件 + 续件计费'];
    }
}
