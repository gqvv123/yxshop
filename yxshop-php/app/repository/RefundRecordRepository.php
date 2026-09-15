<?php

namespace app\repository;

use app\model\RefundRecord;

/**
 * 退款记录仓储类
 */
class RefundRecordRepository extends BaseRepository
{
    protected $model = RefundRecord::class;

    /**
     * 按订单ID获取退款记录
     */
    public function getByOrderId($orderId)
    {
        return $this->query()->where('order_id', $orderId)->get();
    }

    /**
     * 按退款单号查找
     */
    public function findByRefundNo(string $refundNo): ?RefundRecord
    {
        return $this->query()->where('refund_no', $refundNo)->first();
    }

    /**
     * 创建退款记录
     */
    public function createRefund(array $data): RefundRecord
    {
        return $this->create($data);
    }

    /**
     * 更新退款状态
     */
    public function updateStatus(int $id, int $status, string $refundTransactionId = '', string $failedReason = ''): int
    {
        $data = ['refund_status' => $status];
        if ($refundTransactionId) {
            $data['refund_transaction_id'] = $refundTransactionId;
        }
        if ($failedReason) {
            $data['failed_reason'] = $failedReason;
        }

        return $this->query()->where('id', $id)->update($data);
    }
}
