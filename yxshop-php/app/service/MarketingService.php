<?php

namespace app\service;

use app\repository\PromotionRepository;
use app\repository\CouponRepository;
use app\repository\UserCouponRepository;
use app\repository\OrderRepository;
use app\repository\PromOrderRepository;
use app\model\Promotion;
use app\model\Coupon;
use app\model\UserCoupon;
use app\model\Order;
use app\model\PromOrder;
use Exception;

class MarketingService
{
    protected PromotionRepository $promotionRepo;
    protected CouponRepository $couponRepo;
    protected UserCouponRepository $userCouponRepo;
    protected OrderRepository $orderRepo;
    protected PromOrderRepository $promOrderRepo;

    public function __construct()
    {
        $this->promotionRepo = new PromotionRepository();
        $this->couponRepo = new CouponRepository();
        $this->userCouponRepo = new UserCouponRepository();
        $this->orderRepo = new OrderRepository();
        $this->promOrderRepo = new PromOrderRepository();
    }

    public function getSummary(int $appId): array
    {
        try {
            $now = time();
            $activePromotions = $this->promotionRepo->query()
                ->where('app_id', $appId)
                ->where('status', 1)
                ->where('end_time', '>', $now)
                ->count();

            $totalReach = (int) $this->promotionRepo->query()
                ->where('app_id', $appId)
                ->where('status', 1)
                ->sum('views');

            return [
                'active_campaigns' => $activePromotions,
                'total_reach' => $totalReach,
            ];
        } catch (Exception $e) {
            return [
                'active_campaigns' => 0,
                'total_reach' => 0,
            ];
        }
    }

    public function getStatistics(int $appId): array
    {
        try {
            $totalViews = (int) $this->promotionRepo->query()
                ->where('app_id', $appId)->sum('views');

            $promoOrders = $this->promOrderRepo->query()
                ->where('app_id', $appId)->count();
            $conversionRate = $totalViews > 0 ? round($promoOrders / $totalViews * 100, 2) : 0;

            $couponUsage = $this->userCouponRepo->query()
                ->where('app_id', $appId)
                ->where('status', UserCoupon::STATUS_USED)
                ->count();

            $promoRevenue = $this->orderRepo->query()
                ->where('app_id', $appId)
                ->whereNotNull('promotion_type')
                ->where('promotion_type', '>', 0)
                ->sum('pay_price');

            $promoCost = $this->promotionRepo->query()
                ->where('app_id', $appId)->sum('cost');
            $roi = $promoCost > 0 ? round((float) $promoRevenue / (float) $promoCost, 2) : 0;

            return [
                'reach' => $totalViews,
                'conversion_rate' => $conversionRate,
                'coupon_usage' => $couponUsage,
                'roi' => $roi,
                'reach_growth' => 0,
                'conversion_growth' => 0,
                'coupon_usage_growth' => 0,
                'roi_growth' => 0,
            ];
        } catch (Exception $e) {
            return [
                'reach' => 0,
                'conversion_rate' => 0,
                'coupon_usage' => 0,
                'roi' => 0,
                'reach_growth' => 0,
                'conversion_growth' => 0,
                'coupon_usage_growth' => 0,
                'roi_growth' => 0,
            ];
        }
    }

    public function getCampaigns(int $appId, array $params): array
    {
        try {
            $page = (int) ($params['page'] ?? 1);
            $pageSize = (int) ($params['page_size'] ?? 20);

            $query = $this->promotionRepo->query()->where('app_id', $appId);

            if (!empty($params['keyword'])) {
                $query->where('title', 'like', '%' . $params['keyword'] . '%');
            }
            if (isset($params['status']) && $params['status'] !== '') {
                $query->where('status', (int) $params['status']);
            }
            if (isset($params['type']) && $params['type'] !== '') {
                $query->where('type', (int) $params['type']);
            }

            $total = $query->count();
            $list = $query->orderBy('id', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            $items = [];
            foreach ($list as $item) {
                $orderCount = $this->promOrderRepo->query()
                    ->where('prom_id', $item->id)->count();
                $items[] = [
                    'id' => $item->id,
                    'name' => $item->title,
                    'type' => $item->type,
                    'status' => $item->status,
                    'start_time' => $item->start_time,
                    'end_time' => $item->end_time,
                    'views' => (int) ($item->views ?? 0),
                    'orders' => $orderCount,
                ];
            }

            return [
                'list' => $items,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ];
        } catch (Exception $e) {
            return [
                'list' => [],
                'total' => 0,
                'page' => 1,
                'page_size' => 20,
            ];
        }
    }

    public function getCoupons(int $appId, array $params): array
    {
        try {
            $page = (int) ($params['page'] ?? 1);
            $pageSize = (int) ($params['page_size'] ?? 20);

            $query = $this->couponRepo->query()->where('app_id', $appId);

            if (isset($params['status']) && $params['status'] !== '') {
                $query->where('status', (int) $params['status']);
            }

            $total = $query->count();
            $list = $query->orderBy('id', 'desc')
                ->offset(($page - 1) * $pageSize)
                ->limit($pageSize)
                ->get();

            $items = [];
            foreach ($list as $item) {
                $discountValue = $item->type == Coupon::TYPE_DISCOUNT
                    ? $item->discount_rate
                    : $item->discount_amount;

                $items[] = [
                    'id' => $item->id,
                    'coupon_name' => $item->name,
                    'discount_type' => $item->type,
                    'discount_value' => $discountValue,
                    'min_order_amount' => (float) $item->min_amount,
                    'used_count' => (int) $item->used_quantity,
                    'total_count' => (int) $item->total_quantity,
                    'status' => $item->status,
                ];
            }

            return [
                'list' => $items,
                'total' => $total,
                'page' => $page,
                'page_size' => $pageSize,
            ];
        } catch (Exception $e) {
            return [
                'list' => [],
                'total' => 0,
                'page' => 1,
                'page_size' => 20,
            ];
        }
    }

    public function getChannels(int $appId): array
    {
        try {
            $channels = $this->orderRepo->query()
                ->where('app_id', $appId)
                ->whereNotNull('source')
                ->selectRaw('source, COUNT(*) as order_count, SUM(pay_price) as revenue')
                ->groupBy('source')
                ->get();

            if ($channels->isEmpty()) {
                return [];
            }

            $sourceMap = [
                10 => '小程序',
                20 => 'H5',
                30 => 'APP',
                40 => '公众号',
            ];

            $result = [];
            $totalOrders = $channels->sum('order_count');
            foreach ($channels as $ch) {
                $sourceName = $sourceMap[$ch->source] ?? '其他';
                $conversion = $totalOrders > 0 ? round($ch->order_count / $totalOrders * 100, 2) : 0;
                $result[] = [
                    'name' => $sourceName,
                    'reach' => (int) $ch->order_count,
                    'conversion' => $conversion,
                    'revenue' => round((float) $ch->revenue, 2),
                ];
            }

            return $result;
        } catch (Exception $e) {
            return [];
        }
    }

    public function getInsights(int $appId): array
    {
        try {
            $topCampaigns = $this->promotionRepo->query()
                ->where('app_id', $appId)
                ->withCount('promOrders')
                ->orderByDesc('prom_orders_count')
                ->limit(3)
                ->get()
                ->map(function ($p) {
                    return [
                        'id' => $p->id,
                        'name' => $p->title,
                        'type' => $p->type,
                        'orders' => $p->prom_orders_count,
                        'views' => (int) ($p->views ?? 0),
                    ];
                })
                ->values()
                ->toArray();

            $topCoupons = $this->couponRepo->query()
                ->where('app_id', $appId)
                ->orderByDesc('used_quantity')
                ->limit(3)
                ->get()
                ->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'name' => $c->name,
                        'used_count' => (int) $c->used_quantity,
                        'total_count' => (int) $c->total_quantity,
                    ];
                })
                ->values()
                ->toArray();

            $channels = $this->getChannels($appId);

            return [
                'top_performing_campaigns' => $topCampaigns,
                'coupon_performance' => $topCoupons,
                'channel_comparison' => $channels,
            ];
        } catch (Exception $e) {
            return [
                'top_performing_campaigns' => [],
                'coupon_performance' => [],
                'channel_comparison' => [],
            ];
        }
    }

    public function exportData(int $appId): string
    {
        try {
            $campaigns = $this->promotionRepo->query()
                ->where('app_id', $appId)
                ->orderBy('id', 'desc')
                ->get();

            $coupons = $this->couponRepo->query()
                ->where('app_id', $appId)
                ->orderBy('id', 'desc')
                ->get();

            $tmpDir = runtime_path() . 'tmp';
            if (!is_dir($tmpDir)) {
                mkdir($tmpDir, 0755, true);
            }

            $filename = 'marketing_export_' . $appId . '_' . date('YmdHis') . '.csv';
            $filepath = $tmpDir . DIRECTORY_SEPARATOR . $filename;

            $fp = fopen($filepath, 'w');
            fwrite($fp, "\xEF\xBB\xBF");

            fputcsv($fp, ['=== 促销活动 ===']);
            fputcsv($fp, ['ID', '名称', '类型', '状态', '开始时间', '结束时间', '浏览量', '订单数']);
            foreach ($campaigns as $item) {
                $orderCount = $this->promOrderRepo->query()
                    ->where('prom_id', $item->id)->count();
                fputcsv($fp, [
                    $item->id,
                    $item->title,
                    $item->type,
                    $item->status,
                    $item->start_time ? date('Y-m-d H:i:s', (int) $item->start_time) : '',
                    $item->end_time ? date('Y-m-d H:i:s', (int) $item->end_time) : '',
                    $item->views ?? 0,
                    $orderCount,
                ]);
            }

            fputcsv($fp, []);
            fputcsv($fp, ['=== 优惠券 ===']);
            fputcsv($fp, ['ID', '名称', '折扣类型', '折扣值', '最低订单金额', '已用数量', '总数量', '状态']);
            foreach ($coupons as $item) {
                $discountValue = $item->type == Coupon::TYPE_DISCOUNT
                    ? $item->discount_rate
                    : $item->discount_amount;
                fputcsv($fp, [
                    $item->id,
                    $item->name,
                    $item->type,
                    $discountValue,
                    $item->min_amount,
                    $item->used_quantity,
                    $item->total_quantity,
                    $item->status,
                ]);
            }

            fclose($fp);

            return $filepath;
        } catch (Exception $e) {
            throw $e;
        }
    }
}
