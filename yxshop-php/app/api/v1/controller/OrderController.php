<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\OrderService;
use app\validate\OrderValidate;
use app\exception\ValidationException;

class OrderController extends BaseController
{
    /** @var OrderService */
    protected $orderService;

    public function __construct()
    {
        $this->orderService = new OrderService();
    }

    /**
     * 创建订单
     *
     * 入参约定：
     *   address_id  必填，收货地址ID
     *   app_id      必填，应用ID
     *   items       必填，[{item_id, quantity, spec_id?, spec_key?, spec_key_name?}]
     *   coupon_id?  可选
     *   remark?     可选
     */
    public function create(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $data = $request->post();

        $validate = new OrderValidate();
        if (!$validate->scene('api_create')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        if (empty($data['address_id'])) {
            return $this->error('收货地址不能为空');
        }
        if (empty($data['items']) || !is_array($data['items'])) {
            return $this->error('订单商品不能为空');
        }
        if (empty($data['app_id'])) {
            // 兜底从中间件携带的 user 上读取 app_id
            $user = $request->user ?? null;
            $data['app_id'] = $user->app_id ?? 0;
        }
        if (empty($data['app_id'])) {
            return $this->error('app_id 不能为空');
        }

        $data['user_id'] = $userId;
        // OrderService 失败时抛 Exception，由全局 ExceptionHandler 转为 JSON
        $order = $this->orderService->createOrder($data);
        return $this->success($order, '订单创建成功');
    }

    /**
     * 获取订单列表（支持 tab 过滤）
     *
     * GET /api/v1/order/list?tab=pending_payment&page=1&page_size=20
     * tab: all|pending_payment|pending_shipment|pending_receipt|pending_review|refunding
     *
     * 兼容旧接口：status=10&20 等原始 status 值
     */
    public function getList(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $page = (int) $request->get('page', 1);
        $pageSize = (int) $request->get('page_size', 20);
        $status = $request->get('status');
        $statusFilter = ($status === null || $status === '') ? null : (int) $status;
        $tab = $request->get('tab');
        if ($tab === 'all') {
            $tab = null;
        }

        $result = $this->orderService->getOrderList($userId, $page, $pageSize, $statusFilter, $tab);
        return $this->success($result);
    }

    /**
     * 获取订单各状态数量（用于 tab 角标）
     *
     * GET /api/v1/order/status-counts
     */
    public function statusCounts(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $counts = $this->orderService->getOrderStatusCounts((int) $userId);
        return $this->success($counts);
    }

    /**
     * 获取订单详情
     */
    public function getDetail(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->get('id');
        if (!$orderId) {
            return $this->error('订单ID不能为空');
        }

        $order = $this->orderService->getOrderDetail($orderId, $userId);
        return $this->success($order);
    }

    /**
     * 确认收货
     */
    public function confirm(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->post('id');
        if (!$orderId) {
            return $this->error('订单ID不能为空');
        }

        $this->orderService->confirmOrder($orderId, $userId);
        return $this->success(null, '已确认收货');
    }

    /**
     * 取消订单
     */
    public function cancel(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->post('id');
        if (!$orderId) {
            return $this->error('订单ID不能为空');
        }
        $reason = (string) $request->post('reason', '');

        $this->orderService->cancelOrder($orderId, $userId, $reason);
        return $this->success(null, '订单已取消');
    }

    /**
     * 申请退款
     */
    public function refund(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->post('id') ?: $request->post('order_id');
        if (!$orderId) {
            return $this->error('订单ID不能为空');
        }
        $reason = (string) ($request->post('reason') ?: $request->post('refund_reason') ?: '');

        $this->orderService->refundOrder($orderId, $userId, $reason);
        return $this->success(null, '已提交退款申请');
    }

    /**
     * 评价订单
     */
    public function review(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->post('id');
        if (!$orderId) {
            return $this->error('订单ID不能为空');
        }

        $data = $request->post();
        if (empty($data['rating'])) {
            return $this->error('评分不能为空');
        }
        if (empty($data['content'])) {
            return $this->error('评价内容不能为空');
        }

        $data['user_id'] = $userId;
        $this->orderService->reviewOrder($orderId, $data);
        return $this->success(null, '评价成功');
    }

    /**
     * 退款详情
     */
    public function refundDetail(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->get('id');
        if (!$orderId) {
            return $this->error('订单ID不能为空');
        }

        $order = $this->orderService->getOrderDetail($orderId, $userId);

        $refundStatus = $order->pay_status ?? 0;
        $timeline = [];

        if ($order->pay_time) {
            $timeline[] = ['label' => '支付成功', 'time' => $order->pay_time, 'done' => true];
        }
        if (in_array($refundStatus, [\app\model\Order::REFUND_STATUS_REFUNDING, \app\model\Order::REFUND_STATUS_REFUNDED])) {
            $timeline[] = ['label' => '退款申请已提交', 'time' => $order->updated_at ?? time(), 'done' => true];
        }
        if ($refundStatus == \app\model\Order::REFUND_STATUS_REFUNDED) {
            $timeline[] = ['label' => '退款成功', 'time' => $order->updated_at ?? time(), 'done' => true];
        }

        return $this->success([
            'order_id' => $orderId,
            'refund_status' => $refundStatus,
            'refund_amount' => $order->pay_price ?? 0,
            'timeline' => $timeline,
        ]);
    }

    /**
     * 再次购买 — 将历史订单商品加入购物车
     *
     * POST /api/v1/order/reorder  id=1
     */
    public function reorder(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->post('id', '');
        if (empty($orderId)) {
            return $this->error('订单ID不能为空');
        }

        try {
            $result = $this->orderService->reorder($orderId, (int) $userId);
            return $this->success($result, '已加入购物车');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取订单物流信息
     *
     * GET /api/v1/order/logistics?id=1
     */
    public function logistics(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->get('id', '');
        if (empty($orderId)) {
            return $this->error('订单ID不能为空');
        }

        try {
            $result = $this->orderService->getLogistics($orderId, (int) $userId);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 提醒发货
     *
     * POST /api/v1/order/remind-ship  id=1
     */
    public function remindShip(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->post('id', '');
        if (empty($orderId)) {
            return $this->error('订单ID不能为空');
        }

        try {
            $result = $this->orderService->remindShip($orderId, (int) $userId);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除订单（已完成/已取消订单，软删除）
     *
     * POST /api/v1/order/delete  id=1
     */
    public function delete(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }
        $orderId = $request->post('id', '');
        if (empty($orderId)) {
            return $this->error('订单ID不能为空');
        }

        try {
            $this->orderService->deleteOrder($orderId, (int) $userId);
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
