<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\CouponService;
use app\service\UserService;
use app\model\Coupon;
use app\model\UserCoupon;

class CouponController extends BaseController
{
    protected $couponService;
    protected $userService;

    public function __construct()
    {
        $this->couponService = new CouponService();
        $this->userService = new UserService();
    }

    /**
     * 优惠券列表（基础）
     */
    public function getList(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);
        $ids = $request->get('ids');

        $list = $this->couponService->getList($appId, $ids);

        return $this->success($list);
    }

    /**
     * 可领取优惠券列表（含领取状态、剩余数量）
     *
     * GET /api/v1/coupon/available
     */
    public function available(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        $appId = (int) $request->get('app_id', 0);

        try {
            $coupons = $this->couponService->getAvailableCoupons($appId);

            $list = $coupons->map(function ($coupon) use ($userId) {
                $received = 0;
                if ($userId) {
                    $received = $this->couponService->countUserReceived($userId, $coupon->id);
                }

                $remaining = $coupon->total_quantity > 0
                    ? max(0, $coupon->total_quantity - $coupon->used_quantity)
                    : -1; // -1 = 不限量

                return [
                    'id'              => $coupon->id,
                    'name'            => $coupon->name,
                    'type'            => $coupon->type,
                    'type_text'       => $this->getCouponTypeText($coupon->type),
                    'color'           => $coupon->color,
                    'discount_amount' => (float) $coupon->discount_amount,
                    'discount_rate'   => (int) $coupon->discount_rate,
                    'min_amount'      => (float) $coupon->min_amount,
                    'min_amount_text' => $coupon->min_amount > 0 ? '满' . $coupon->min_amount . '元可用' : '无门槛',
                    'expiry_type'     => $coupon->expiry_type,
                    'expiry_text'     => $this->getExpiryText($coupon),
                    'scope'           => $coupon->scope,
                    'total_quantity'  => (int) $coupon->total_quantity,
                    'used_quantity'   => (int) $coupon->used_quantity,
                    'remaining'       => $remaining,
                    'can_receive'     => $coupon->canReceive(),
                    'user_received'   => $received,
                    'user_limit_count'=> (int) ($coupon->user_limit_count ?? 1),
                ];
            });

            return $this->success($list);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 领取优惠券
     *
     * POST /api/v1/coupon/claim  coupon_id=1
     */
    public function claim(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $couponId = (int) $request->post('coupon_id', 0);
        if ($couponId <= 0) {
            return $this->error('优惠券ID不能为空');
        }

        try {
            $user = $this->userService->findOrFail($userId);
            $ip = $request->getRealIp();
            $userCoupon = $this->couponService->receiveCoupon(
                $couponId,
                (int) $userId,
                (int) ($user->app_id ?? 0),
                $ip
            );

            return $this->success([
                'user_coupon_id' => $userCoupon->id,
            ], '领取成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 优惠券统计（未使用/已使用/已过期 各数量）
     *
     * GET /api/v1/coupon/stats
     */
    public function stats(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $stats = $this->couponService->getUserCouponStats($userId);

        return $this->success([
            'unused'  => $stats['unused'] ?? 0,
            'used'    => $stats['used'] ?? 0,
            'expired' => $stats['expired'] ?? 0,
            'total'   => ($stats['unused'] ?? 0) + ($stats['used'] ?? 0) + ($stats['expired'] ?? 0),
        ]);
    }

    /**
     * 我的优惠券（增强版 — 补充使用门槛/适用范围/有效期格式化）
     *
     * GET /api/v1/coupon/my-list?status=unused&page=1&page_size=20
     */
    public function myList(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $status = $request->get('status', 'unused');
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 20)));

        $result = $this->couponService->getMyCouponsPaginated($userId, $status, $page, $pageSize);

        // 增强：格式化输出
        $items = collect($result->items())->map(function ($uc) {
            $coupon = $uc->coupon;
            if (!$coupon) {
                return null;
            }
            return [
                'user_coupon_id'  => $uc->id,
                'coupon_id'       => $coupon->id,
                'name'            => $coupon->name,
                'type'            => $coupon->type,
                'type_text'       => $this->getCouponTypeText($coupon->type),
                'color'           => $coupon->color,
                'discount_amount' => (float) $coupon->discount_amount,
                'discount_rate'   => (int) $coupon->discount_rate,
                'min_amount'      => (float) $coupon->min_amount,
                'min_amount_text' => $coupon->min_amount > 0 ? '满' . $coupon->min_amount . '元可用' : '无门槛',
                'scope'           => $coupon->scope,
                'scope_text'      => $coupon->scope == Coupon::SCOPE_ALL ? '全场通用' : '指定商品',
                'expiry_text'     => $this->getExpiryText($coupon),
                'status'          => $uc->status,
                'status_text'     => [UserCoupon::STATUS_UNUSED => '未使用', UserCoupon::STATUS_USED => '已使用', UserCoupon::STATUS_EXPIRED => '已过期'][$uc->status] ?? '未知',
                'use_time'        => $uc->use_time ?? 0,
                'created_at'      => $uc->created_at,
            ];
        })->filter()->values();

        return $this->success([
            'list'      => $items,
            'total'     => $result->total(),
            'page'      => $result->currentPage(),
            'page_size' => $result->perPage(),
            'last_page' => $result->lastPage(),
        ]);
    }

    // ============================================================
    // 私有辅助
    // ============================================================

    private function getCouponTypeText($type): string
    {
        return [
            Coupon::TYPE_MONEY   => '满减券',
            Coupon::TYPE_DISCOUNT=> '折扣券',
            Coupon::TYPE_GIFT    => '礼品券',
        ][$type] ?? '优惠券';
    }

    private function getExpiryText($coupon): string
    {
        if ($coupon->expiry_type == Coupon::EXPIRY_AFTER_RECEIVE) {
            return '领取后' . $coupon->expiry_days . '天有效';
        }
        if ($coupon->expiry_type == Coupon::EXPIRY_FIXED_TIME) {
            $start = date('Y-m-d', $coupon->start_at);
            $end = date('Y-m-d', $coupon->end_at);
            return $start . ' ~ ' . $end;
        }
        return '永久有效';
    }
}
