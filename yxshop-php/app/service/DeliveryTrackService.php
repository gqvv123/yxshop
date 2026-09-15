<?php

namespace app\service;

use app\repository\OrderDeliveryRepository;
use app\exception\BusinessException;
use Exception;

class DeliveryTrackService extends BaseService
{
    public function __construct(OrderDeliveryRepository $repository)
    {
        parent::__construct($repository);
    }

    public function getTrackInfo($orderId)
    {
        try {
            $this->logInfo('获取物流追踪信息开始', ['order_id' => $orderId]);

            $delivery = $this->repository->findByOrderId($orderId);

            if (!$delivery) {
                throw new BusinessException('未找到该订单的物流信息');
            }

            $trackLogs = $delivery->trackLogs()->orderBy('track_time', 'desc')->get();

            $result = [
                'delivery' => $delivery,
                'track_logs' => $trackLogs,
            ];

            $this->logInfo('获取物流追踪信息成功', ['order_id' => $orderId]);
            return $result;

        } catch (Exception $e) {
            $this->logError('获取物流追踪信息失败', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function addTrackLog($orderId, array $data)
    {
        try {
            $this->logInfo('添加物流追踪记录开始', ['order_id' => $orderId]);

            $delivery = $this->repository->findByOrderId($orderId);

            if (!$delivery) {
                throw new BusinessException('未找到该订单的物流信息');
            }

            $trackLog = $delivery->trackLogs()->create([
                'content' => $data['content'],
                'track_time' => $data['track_time'],
                'created_at' => time(),
            ]);

            $this->logInfo('添加物流追踪记录成功', ['order_id' => $orderId]);
            return $trackLog;

        } catch (Exception $e) {
            $this->logError('添加物流追踪记录失败', [
                'order_id' => $orderId,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getTrackLogs($orderId)
    {
        try {
            $this->logInfo('获取物流追踪记录列表开始', ['order_id' => $orderId]);

            $delivery = $this->repository->findByOrderId($orderId);

            if (!$delivery) {
                throw new BusinessException('未找到该订单的物流信息');
            }

            $trackLogs = $delivery->trackLogs()->orderBy('track_time', 'desc')->get();

            $this->logInfo('获取物流追踪记录列表成功', ['order_id' => $orderId]);
            return $trackLogs;

        } catch (Exception $e) {
            $this->logError('获取物流追踪记录列表失败', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function updateTrackStatus($orderId, $status)
    {
        try {
            $this->logInfo('更新物流状态开始', ['order_id' => $orderId, 'status' => $status]);

            $delivery = $this->repository->findByOrderId($orderId);

            if (!$delivery) {
                throw new BusinessException('未找到该订单的物流信息');
            }

            $delivery->status = $status;
            $delivery->updated_at = time();
            $delivery->save();

            $this->logInfo('更新物流状态成功', ['order_id' => $orderId, 'status' => $status]);
            return $delivery;

        } catch (Exception $e) {
            $this->logError('更新物流状态失败', [
                'order_id' => $orderId,
                'status' => $status,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
