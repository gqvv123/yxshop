<?php

namespace app\service;

use app\repository\OrderLogRepository;
use Exception;

class OrderLogService extends BaseService
{
    public function __construct(?OrderLogRepository $repository = null)
    {
        parent::__construct($repository ?? new OrderLogRepository());
    }

    public function getOrderLogs($orderId, $page = 1, $pageSize = 20)
    {
        try {
            return $this->repository->paginatedByOrder($orderId, (int) $page, (int) $pageSize);
        } catch (Exception $e) {
            $this->logError('获取订单日志失败', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function addLog($orderId, $userId, $userType, $action, $field, $oldValue, $newValue, $remark = '')
    {
        try {
            $log = $this->repository->create([
                'order_id' => $orderId,
                'user_id' => $userId,
                'user_type' => $userType,
                'action' => $action,
                'field' => $field,
                'old_value' => $oldValue,
                'new_value' => $newValue,
                'remark' => $remark,
                'created_at' => time(),
                'updated_at' => time(),
            ]);
            return $log;
        } catch (Exception $e) {
            $this->logError('添加订单日志失败', ['order_id' => $orderId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getRecentLogs($appId = 0, $limit = 50)
    {
        try {
            return $this->repository->listRecentByApp((int) $appId, (int) $limit);
        } catch (Exception $e) {
            $this->logError('获取最近订单日志失败', ['app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->paginatedByApp((int) $appId, (int) $pageSize, 'created_at', 'desc');
    }
}
