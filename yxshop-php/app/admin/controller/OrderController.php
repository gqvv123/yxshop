<?php

namespace app\admin\controller;

use support\Request;
use app\service\OrderService;
use app\validate\OrderValidate;
use app\exception\ValidationException;

class OrderController extends BaseController
{

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $params = [
            'search' => $request->input('search'),
            'date' => $request->input('date'),
            'status' => $request->input('status'),
            'pay_status' => $request->input('pay_status'),
            'delivery_status' => $request->input('delivery_status'),
            'start_date' => $request->input('start_date'),
            'end_date' => $request->input('end_date'),
        ];
        $pageSize = (int) $request->get('page_size', 20);

        $orders = $this->orderService->getAdminOrderList($appId, $params, $pageSize);

        return $this->paginate($orders);
    }

    public function show(Request $request, $id)
    {
        $order = $this->orderService->getAdminOrderDetails($id);
        if (!$order) {
            return $this->errorNotFound('订单不存在');
        }

        return $this->success($order);
    }

    public function statistics(Request $request)
    {
        $appId = $this->getAppId($request);
        $data = $this->orderService->getStatistics($appId);
        return $this->success($data);
    }

    public function showTracking(Request $request, $id)
    {
        if (empty($id)) {
            return $this->error('订单ID不能为空');
        }
        try {
            $data = $this->orderService->getOrderTracking((int) $id);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function customerProfile(Request $request, $id)
    {
        if (empty($id)) {
            return $this->error('订单ID不能为空');
        }
        try {
            $data = $this->orderService->getCustomerProfile((int) $id);
            return $this->success($data);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function updateStatus(Request $request, $id)
    {
        if (empty($id)) {
            return $this->error('订单ID不能为空');
        }

        $status = $request->post('status');
        $paymentStatus = $request->post('payment_status');

        if ($status === null && $paymentStatus === null) {
            return $this->error('状态参数不能为空');
        }

        $this->orderService->updateOrderStatus($id, $status, $paymentStatus);
        return $this->success(null, '订单状态更新成功');
    }

    public function updateProductStatus(Request $request)
    {
        $id = $request->post('id');
        $status = $request->post('status');

        if (empty($id)) {
            return $this->error('订单产品ID不能为空');
        }

        if ($status === null) {
            return $this->error('状态参数不能为空');
        }

        $result = $this->orderService->updateOrderItemStatus($id, $status);
        if (!$result) {
            return $this->error('订单产品不存在');
        }
        return $this->success(null, '产品状态更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->orderService->adminDeleteOrder($id);
        return $this->success(null, '订单删除成功');
    }

    public function tracking(Request $request)
    {
        $appId = $this->getAppId($request);
        $orders = $this->orderService->getTrackingOrders($appId);
        return $this->success($orders);
    }

    public function changePrice(Request $request, $id)
    {
        if (empty($id)) {
            return $this->error('订单ID不能为空');
        }

        $updatePrice = $request->post('update_price');
        $reason = $request->post('reason', '');
        $admin = $request->admin ?? [];

        if ($updatePrice === null || !is_numeric($updatePrice)) {
            return $this->error('改价金额必须为数字');
        }

        $order = $this->orderService->changePrice(
            $id,
            $updatePrice,
            $reason,
            20,
            $admin['id'] ?? 0,
            $admin['username'] ?? ''
        );
        return $this->success($order, '改价成功');
    }

    public function freeShipping(Request $request, $id)
    {
        $reason = $request->post('reason', '');
        $admin = $request->admin ?? [];

        $order = $this->orderService->freeShipping(
            $id,
            $reason,
            20,
            $admin['id'] ?? 0,
            $admin['username'] ?? ''
        );
        return $this->success($order, '免邮成功');
    }

    public function ship(Request $request, $id)
    {
        if (empty($id)) {
            return $this->error('订单ID不能为空');
        }

        $expressId = $request->post('express_id', 0);
        $expressNo = $request->post('express_no', '');
        $admin = $request->admin ?? [];

        if (empty($expressNo)) {
            return $this->error('快递单号不能为空');
        }

        $order = $this->orderService->ship(
            $id,
            $expressId,
            $expressNo,
            20,
            $admin['id'] ?? 0,
            $admin['username'] ?? ''
        );
        return $this->success($order, '发货成功');
    }

    public function audit(Request $request, $id)
    {
        $result = $request->post('result');
        $remark = $request->post('remark', '');
        $admin = $request->admin ?? [];

        $order = $this->orderService->audit(
            $id,
            $result,
            $remark,
            20,
            $admin['id'] ?? 0,
            $admin['username'] ?? ''
        );
        return $this->success($order, '审核成功');
    }

    public function refund(Request $request, $id)
    {
        if (empty($id)) {
            return $this->error('订单ID不能为空');
        }

        $refundAmount = $request->post('refund_amount');
        $reason = $request->post('reason', '');
        $admin = $request->admin ?? [];

        if ($refundAmount === null || !is_numeric($refundAmount) || $refundAmount <= 0) {
            return $this->error('退款金额必须为大于0的数字');
        }

        $order = $this->orderService->refund(
            $id,
            (float) $refundAmount,
            $reason,
            20,
            $admin['id'] ?? 0,
            $admin['username'] ?? ''
        );
        return $this->success($order, '退款申请成功');
    }

    public function note(Request $request, $id)
    {
        $note = $request->post('note', $request->post('content', ''));
        $append = $request->post('append', false);
        $admin = $request->admin ?? [];

        $order = $this->orderService->addNote(
            $id,
            $note,
            $append,
            20,
            $admin['id'] ?? 0,
            $admin['username'] ?? ''
        );
        return $this->success($order, '备注成功');
    }
}
