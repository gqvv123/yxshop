<?php

namespace app\service;

use app\repository\RechargeOrderRepository;
use app\model\RechargeOrder;
use app\exception\BusinessException;
use Exception;

/**
 * 充值订单服务类
 *
 * @property RechargeOrderRepository $repository
 */
class RechargeOrderService extends BaseService
{
    public function __construct(?RechargeOrderRepository $repository = null)
    {
        parent::__construct($repository ?? new RechargeOrderRepository());
    }

    public function getByUser(int $userId, int $appId = 0)
    {
        try {
            return $this->repository->getByUser($userId, $appId);
        } catch (Exception $e) {
            $this->logError('按用户获取充值订单失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByPackage(int $packageId, int $appId = 0)
    {
        try {
            return $this->repository->getByPackage($packageId, $appId);
        } catch (Exception $e) {
            $this->logError('按套餐获取充值订单失败', [
                'package_id' => $packageId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByPayStatus(int $payStatus, int $appId = 0)
    {
        try {
            return $this->repository->getByPayStatus($payStatus, $appId);
        } catch (Exception $e) {
            $this->logError('按支付状态获取充值订单失败', [
                'pay_status' => $payStatus,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByStatus(int $status, int $appId = 0)
    {
        try {
            return $this->repository->getByStatus($status, $appId);
        } catch (Exception $e) {
            $this->logError('按状态获取充值订单失败', [
                'status' => $status,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        try {
            return $this->repository->getPaginatedList($appId, $filters, $pageSize);
        } catch (Exception $e) {
            $this->logError('获取充值订单分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getStats(array $conditions = [])
    {
        try {
            return $this->repository->getStats($conditions);
        } catch (Exception $e) {
            $this->logError('获取充值订单统计失败', [
                'app_id' => $conditions['app_id'] ?? 0,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getAmountStats(int $appId = 0): array
    {
        try {
            return $this->repository->getAmountStats($appId);
        } catch (Exception $e) {
            $this->logError('获取充值金额统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建充值订单开始', ['data' => $data]);
            $order = $this->repository->create($data);
            $this->logInfo('创建充值订单成功', ['id' => $order->id]);
            return $order;
        } catch (Exception $e) {
            $this->logError('创建充值订单失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新充值订单开始', ['id' => $id, 'data' => $data]);
            $order = $this->repository->update($id, $data);
            $this->logInfo('更新充值订单成功', ['id' => $id]);
            return $order;
        } catch (Exception $e) {
            $this->logError('更新充值订单失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function markPaid(int $id, string $payMethod = '')
    {
        try {
            $this->logInfo('标记已支付开始', ['id' => $id, 'pay_method' => $payMethod]);
            $order = $this->repository->findOrFail($id);

            if ($order->pay_status === RechargeOrder::PAY_STATUS_PAID) {
                throw new BusinessException('订单已支付');
            }

            $order->pay_status = RechargeOrder::PAY_STATUS_PAID;
            $order->pay_method = $payMethod;
            $order->status = RechargeOrder::STATUS_COMPLETED;
            $order->save();

            $this->logInfo('标记已支付成功', ['id' => $id]);
            return $order;
        } catch (Exception $e) {
            $this->logError('标记已支付失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function cancel(int $id)
    {
        try {
            $this->logInfo('取消充值订单开始', ['id' => $id]);
            $order = $this->repository->findOrFail($id);

            if ($order->pay_status === RechargeOrder::PAY_STATUS_PAID) {
                throw new BusinessException('已支付的订单不能取消');
            }

            $order->status = RechargeOrder::STATUS_CANCELLED;
            $order->save();

            $this->logInfo('取消充值订单成功', ['id' => $id]);
            return $order;
        } catch (Exception $e) {
            $this->logError('取消充值订单失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
