<?php

namespace app\service;

use app\repository\AfterSalesRepository;
use app\repository\RefundRecordRepository;
use app\repository\AfterSalesLogRepository;
use app\model\AfterSales;
use app\model\RefundRecord;
use app\exception\BusinessException;
use support\Db;
use Exception;

/**
 * 售后申请服务
 *
 * 4 层架构：状态校验、事务编排、业务逻辑在 Service 层；
 * Repository 只负责纯数据操作（查询、乐观锁更新、日志写入）。
 *
 * @property AfterSalesRepository $repository
 */
class AfterSalesService extends BaseService
{
    // 暴露状态常量供 Controller 层使用，避免 Controller 直接引用 Model
    const STATUS_PENDING         = AfterSales::STATUS_PENDING;
    const STATUS_APPROVED        = AfterSales::STATUS_APPROVED;
    const STATUS_RETURN_SHIPPED  = AfterSales::STATUS_RETURN_SHIPPED;
    const STATUS_RETURN_RECEIVED = AfterSales::STATUS_RETURN_RECEIVED;
    const STATUS_REFUNDING       = AfterSales::STATUS_REFUNDING;
    const STATUS_COMPLETED        = AfterSales::STATUS_COMPLETED;
    const STATUS_REJECTED         = AfterSales::STATUS_REJECTED;
    const STATUS_CANCELLED        = AfterSales::STATUS_CANCELLED;

    protected OrderService $orderService;
    protected RefundRecordRepository $refundRepo;
    protected AfterSalesLogRepository $logRepo;

    public function __construct(?AfterSalesRepository $repository = null)
    {
        parent::__construct($repository ?? new AfterSalesRepository());
        $this->orderService = new OrderService();
        $this->refundRepo = new RefundRecordRepository();
        $this->logRepo = new AfterSalesLogRepository();
    }

    /**
     * 后台列表（按状态/类型筛选 + 分页）
     */
    public function getList($status = null, $subType = null, int $page = 1, int $limit = 20, int $appId = 0): array
    {
        $statusInt = ($status === null || $status === '') ? null : (int) $status;
        $subTypeInt = ($subType === null || $subType === '') ? null : (int) $subType;
        return $this->repository->getPaginatedList($statusInt, $subTypeInt, $page, $limit, $appId);
    }

    /**
     * 详情（含操作日志 + 退款记录）
     */
    public function getDetail(int $id): ?AfterSales
    {
        try {
            return $this->repository->getDetail($id);
        } catch (Exception $e) {
            $this->logError('获取售后详情失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 处理售后（审核通过，可指定目标状态）
     */
    public function process(int $id, int $targetStatus, int $processedBy, ?string $actorName = null, ?string $sellerRemark = null): AfterSales
    {
        try {
            $this->logInfo('处理售后开始', ['id' => $id, 'target_status' => $targetStatus, 'operator' => $processedBy]);

            $record = $this->repository->findOrFail($id);

            if (!$record->canTransitionTo($targetStatus)) {
                throw new BusinessException('当前状态不允许此操作：' . $record->getStatusText());
            }

            $result = $this->executeStatusTransition($record, $targetStatus, [
                'seller_remark' => $sellerRemark,
            ], $processedBy, $actorName, $sellerRemark, AfterSales::ACTION_APPROVE);

            // 如果是 approved 状态且类型是退款，自动创建退款记录
            if ($targetStatus === AfterSales::STATUS_APPROVED && (int) $record->sub_type === AfterSales::TYPE_REFUND) {
                $this->refundRepo->createRefund([
                    'after_sales_id' => $id,
                    'order_id'       => $record->order_id,
                    'user_id'        => $record->user_id,
                    'refund_amount'  => 0,
                    'refund_type'    => RefundRecord::TYPE_ORIGINAL,
                    'refund_status'  => RefundRecord::STATUS_PENDING,
                    'operator_id'    => $processedBy,
                    'operator_name'  => $actorName,
                    'app_id'         => $record->app_id,
                ]);
            }

            $this->logInfo('处理售后成功', ['id' => $id]);
            return $result;
        } catch (BusinessException $e) {
            $this->logWarning('处理售后业务异常', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('处理售后失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 拒绝售后
     */
    public function reject(int $id, int $processedBy, ?string $actorName = null, ?string $reason = null): AfterSales
    {
        try {
            $this->logInfo('拒绝售后开始', ['id' => $id, 'operator' => $processedBy, 'reason' => $reason]);

            $record = $this->repository->findOrFail($id);

            if (!$record->canTransitionTo(AfterSales::STATUS_REJECTED)) {
                throw new BusinessException('当前状态不允许拒绝：' . $record->getStatusText());
            }

            $result = $this->executeStatusTransition($record, AfterSales::STATUS_REJECTED, [
                'seller_remark' => $reason,
            ], $processedBy, $actorName, $reason, AfterSales::ACTION_REJECT);

            $this->logInfo('拒绝售后成功', ['id' => $id]);
            return $result;
        } catch (BusinessException $e) {
            $this->logWarning('拒绝售后业务异常', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('拒绝售后失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 买家发货（填写快递）
     */
    public function returnShip(int $id, string $expressNo, int $expressId = 0): AfterSales
    {
        try {
            $this->logInfo('买家发货', ['id' => $id, 'express_no' => $expressNo]);

            $record = $this->repository->findOrFail($id);

            if (!$record->canTransitionTo(AfterSales::STATUS_RETURN_SHIPPED)) {
                throw new BusinessException('当前状态不允许发货：' . $record->getStatusText());
            }

            $result = $this->executeStatusTransition($record, AfterSales::STATUS_RETURN_SHIPPED, [
                'express_no'    => $expressNo,
                'express_id'    => $expressId,
                'delivery_time' => time(),
            ], $record->user_id, null, '快递单号：' . $expressNo, AfterSales::ACTION_RETURN_SHIP);

            return $result;
        } catch (BusinessException $e) {
            $this->logWarning('买家发货业务异常', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('买家发货失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 卖家确认收货
     */
    public function receive(int $id, int $processedBy, ?string $actorName = null): AfterSales
    {
        try {
            $this->logInfo('确认收货开始', ['id' => $id, 'operator' => $processedBy]);

            $record = $this->repository->findOrFail($id);

            if (!$record->canTransitionTo(AfterSales::STATUS_RETURN_RECEIVED)) {
                throw new BusinessException('当前状态不允许确认收货：' . $record->getStatusText());
            }

            $result = $this->executeStatusTransition($record, AfterSales::STATUS_RETURN_RECEIVED, [], $processedBy, $actorName, null, AfterSales::ACTION_RECEIVE);

            $this->logInfo('确认收货成功', ['id' => $id]);
            return $result;
        } catch (BusinessException $e) {
            $this->logWarning('确认收货业务异常', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('确认收货失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 发起退款
     */
    public function refund(int $id, int $processedBy, ?string $actorName = null, ?string $remark = null): AfterSales
    {
        try {
            $this->logInfo('发起退款开始', ['id' => $id, 'operator' => $processedBy]);

            $record = $this->repository->findOrFail($id);

            if (!$record->canTransitionTo(AfterSales::STATUS_REFUNDING)) {
                throw new BusinessException('当前状态不允许发起退款：' . $record->getStatusText());
            }

            $result = $this->executeStatusTransition($record, AfterSales::STATUS_REFUNDING, [], $processedBy, $actorName, $remark, AfterSales::ACTION_REFUND);

            $this->logInfo('发起退款成功', ['id' => $id]);
            return $result;
        } catch (BusinessException $e) {
            $this->logWarning('发起退款业务异常', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('发起退款失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 完成售后
     */
    public function complete(int $id, int $processedBy, ?string $actorName = null, ?string $remark = null): AfterSales
    {
        try {
            $this->logInfo('完成售后开始', ['id' => $id, 'operator' => $processedBy]);

            $record = $this->repository->findOrFail($id);

            if (!$record->canTransitionTo(AfterSales::STATUS_COMPLETED)) {
                throw new BusinessException('当前状态不允许完成：' . $record->getStatusText());
            }

            $result = $this->executeStatusTransition($record, AfterSales::STATUS_COMPLETED, [], $processedBy, $actorName, $remark, AfterSales::ACTION_COMPLETE);

            $this->logInfo('完成售后成功', ['id' => $id]);
            return $result;
        } catch (BusinessException $e) {
            $this->logWarning('完成售后业务异常', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('完成售后失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 用户取消
     */
    public function cancel(int $id, int $userId): AfterSales
    {
        try {
            $this->logInfo('用户取消售后', ['id' => $id, 'user_id' => $userId]);

            $record = $this->repository->findOrFail($id);

            if (!$record->canTransitionTo(AfterSales::STATUS_CANCELLED)) {
                throw new BusinessException('当前状态不允许取消：' . $record->getStatusText());
            }

            $result = $this->executeStatusTransition($record, AfterSales::STATUS_CANCELLED, [], $userId, null, null, AfterSales::ACTION_CANCEL);

            return $result;
        } catch (BusinessException $e) {
            $this->logWarning('用户取消售后业务异常', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        } catch (Exception $e) {
            $this->logError('用户取消售后失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * 售后统计
     */
    public function getStatistics(int $appId = 0): array
    {
        return [
            'status_stats'  => $this->repository->getStatusCounts($appId),
            'type_stats'    => $this->repository->getTypeCounts($appId),
            'today_count'   => $this->repository->countToday($appId),
            'pending_count' => $this->repository->countPending($appId),
        ];
    }

    // ============================================================
    // 用户端
    // ============================================================

    /**
     * 用户售后列表
     */
    public function getUserList($userId, $status = null, $appId = 0)
    {
        return $this->repository->getUserAfterSales(
            $userId,
            $status === null || $status === '' ? null : (int) $status,
            $appId
        );
    }

    /**
     * 申请售后
     */
    public function apply(array $data)
    {
        foreach (['user_id', 'order_id', 'sub_type', 'reasons'] as $f) {
            if (empty($data[$f])) {
                throw new BusinessException("{$f} 不能为空");
            }
        }

        return $this->transaction(function () use ($data) {
            $now = time();
            $data = array_merge([
                'status'      => AfterSales::STATUS_PENDING,
                'sub_status'  => 10,
                'app_id'      => $data['app_id'] ?? 0,
                'created_at'  => $now,
                'updated_at'  => $now,
            ], $data);

            $record = $this->repository->create($data);

            $this->logRepo->writeLog(
                $record->id,
                AfterSales::ACTION_APPLY,
                null,
                $record->status,
                $data['user_id'],
                null,
                '用户提交售后申请'
            );

            $this->logInfo('用户提交售后申请', ['id' => $record->id, 'user_id' => $data['user_id']]);
            return $record;
        });
    }

    /**
     * 批量处理（兼容旧调用，限制仅限审核通过）
     */
    public function batchProcess(array $ids, array $data): int
    {
        if (empty($ids)) {
            return 0;
        }
        return $this->repository->batchUpdateByIds($ids, $data);
    }

    /**
     * 全部售后（用于导出）
     */
    public function getAllForExport(int $appId = 0)
    {
        return $this->repository->getAllByApp($appId);
    }

    /**
     * 删除售后（软删除）
     */
    public function delete($id)
    {
        try {
            $this->logInfo('删除售后记录', ['id' => $id]);
            $record = $this->repository->findOrFail($id);
            $this->logRepo->writeLog($id, AfterSales::ACTION_UPDATE, $record->status, null, 0, null, '删除');
            return $record->softDelete();
        } catch (Exception $e) {
            $this->logError('删除售后记录失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    // ============================================================
    // 内部方法
    // ============================================================

    /**
     * 执行状态转换（事务 + 乐观锁 + 日志）
     */
    private function executeStatusTransition(
        AfterSales $record,
        int $targetStatus,
        array $additionalData,
        int $actorId,
        ?string $actorName,
        ?string $remark,
        string $action
    ): AfterSales {
        $beforeStatus = $record->status;
        $currentVersion = $record->version;

        $updateData = array_merge(['status' => $targetStatus], $additionalData);

        return Db::transaction(function () use ($record, $targetStatus, $currentVersion, $updateData, $beforeStatus, $actorId, $actorName, $remark, $action) {
            $affected = $this->repository->updateWithVersionLock(
                (int) $record->id,
                $currentVersion,
                $updateData
            );

            if ($affected === 0) {
                throw new BusinessException('数据已被其他操作修改，请刷新后重试', 409);
            }

            $this->logRepo->writeLog(
                (int) $record->id,
                $action,
                $beforeStatus,
                $targetStatus,
                $actorId,
                $actorName,
                $remark
            );

            return $this->repository->findOrFail($record->id);
        });
    }
}
