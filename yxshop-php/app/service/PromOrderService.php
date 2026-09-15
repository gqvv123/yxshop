<?php

namespace app\service;

use app\repository\PromOrderRepository;
use app\model\PromOrder;
use app\exception\BusinessException;
use Exception;

/**
 * 促销订单服务类
 *
 * @property PromOrderRepository $repository
 */
class PromOrderService extends BaseService
{
    public function __construct(PromOrderRepository $repository = null)
    {
        $repository = $repository ?? new PromOrderRepository();
        parent::__construct($repository);
    }

    public function getPaginatedList(int $appId, int $perPage = 20)
    {
        return $this->repository->paginatedByApp($appId, $perPage, 'id', 'desc');
    }

    /**
     * 创建促销订单
     */
    public function createPromOrder($promId, $orderId, $userId, $promType, $promDiscount, $promAmount, $appId = 0)
    {
        try {
            $this->logInfo('创建促销订单开始', [
                'prom_id' => $promId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'prom_type' => $promType,
                'app_id' => $appId
            ]);

            $promOrder = $this->repository->create([
                'prom_id' => $promId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'prom_type' => $promType,
                'prom_discount' => $promDiscount,
                'prom_amount' => $promAmount,
                'prom_status' => PromOrder::STATUS_PENDING,
                'prom_time' => time(),
                'app_id' => $appId,
            ]);

            $this->logInfo('创建促销订单成功', ['id' => $promOrder->id]);
            return $promOrder;

        } catch (Exception $e) {
            $this->logError('创建促销订单失败', [
                'prom_id' => $promId,
                'order_id' => $orderId,
                'user_id' => $userId,
                'prom_type' => $promType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取促销订单列表
     */
    public function getPromOrderList($promId = null, $userId = null, $promType = null, $promStatus = null, $appId = 0, $limit = 20)
    {
        try {
            return $this->repository->getPromOrders($promId, $userId, $promType, $promStatus, $appId, $limit);

        } catch (Exception $e) {
            $this->logError('获取促销订单列表失败', [
                'prom_id' => $promId,
                'user_id' => $userId,
                'prom_type' => $promType,
                'prom_status' => $promStatus,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取促销订单统计
     */
    public function getPromOrderStats($startTime = null, $endTime = null, $appId = 0)
    {
        try {
            return $this->repository->getPromOrderStats($startTime, $endTime, $appId);

        } catch (Exception $e) {
            $this->logError('获取促销订单统计失败', [
                'start_time' => $startTime,
                'end_time' => $endTime,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户促销订单统计
     */
    public function getUserPromOrderStats($userId, $appId = 0)
    {
        try {
            return $this->repository->getUserPromOrderStats($userId, $appId);

        } catch (Exception $e) {
            $this->logError('获取用户促销订单统计失败', [
                'user_id' => $userId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新促销订单状态
     */
    public function updatePromOrderStatus($id, $status)
    {
        try {
            $this->logInfo('更新促销订单状态开始', ['id' => $id, 'status' => $status]);

            $promOrder = $this->repository->findOrFail($id);

            $promOrder->prom_status = $status;
            $promOrder->save();

            $this->logInfo('更新促销订单状态成功', ['id' => $id, 'status' => $status]);
            return $promOrder;

        } catch (Exception $e) {
            $this->logError('更新促销订单状态失败', [
                'id' => $id,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 处理促销订单
     */
    public function processPromOrder($id)
    {
        try {
            $this->logInfo('处理促销订单开始', ['id' => $id]);

            $promOrder = $this->repository->findOrFail($id);

            if ($promOrder->prom_status != PromOrder::STATUS_PENDING) {
                throw new BusinessException('该促销订单已处理');
            }

            // 根据促销类型处理
            switch ($promOrder->prom_type) {
                case PromOrder::TYPE_DISCOUNT:
                    $this->processDiscountProm($promOrder);
                    break;
                case PromOrder::TYPE_COUPON:
                    $this->processCouponProm($promOrder);
                    break;
                case PromOrder::TYPE_GIFT:
                    $this->processGiftProm($promOrder);
                    break;
                case PromOrder::TYPE_POINTS:
                    $this->processPointsProm($promOrder);
                    break;
                default:
                    throw new BusinessException('不支持的促销类型');
            }

            // 更新状态为成功
            $promOrder->prom_status = PromOrder::STATUS_SUCCESS;
            $promOrder->save();

            $this->logInfo('处理促销订单成功', ['id' => $id]);
            return $promOrder;

        } catch (Exception $e) {
            $this->logError('处理促销订单失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 处理折扣促销
     */
    private function processDiscountProm($promOrder)
    {
        // 折扣促销逻辑
        $this->logInfo('处理折扣促销', ['order_id' => $promOrder->order_id]);
    }

    /**
     * 处理优惠券促销
     */
    private function processCouponProm($promOrder)
    {
        // 优惠券促销逻辑
        $this->logInfo('处理优惠券促销', ['order_id' => $promOrder->order_id]);
    }

    /**
     * 处理赠品促销
     */
    private function processGiftProm($promOrder)
    {
        // 赠品促销逻辑
        $this->logInfo('处理赠品促销', ['order_id' => $promOrder->order_id]);
    }

    /**
     * 处理积分促销
     */
    private function processPointsProm($promOrder)
    {
        // 积分促销逻辑
        $this->logInfo('处理积分促销', ['order_id' => $promOrder->order_id]);
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        $page = (int) request()->get('page', 1);
        return $this->repository->getPaginatedListWithRelations((int) $appId, (int) $pageSize, $keyword, $page);
    }
}
