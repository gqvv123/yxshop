<?php

namespace app\repository;

use app\model\InvoiceHeader;
use app\model\InvoiceRecord;

/**
 * 发票数据仓储。
 *
 * @package app\repository
 */
class InvoiceRepository extends BaseRepository
{
    protected $model = InvoiceHeader::class;

    /**
     * 用户的发票抬头列表
     */
    public function getUserHeaders(int $userId, int $appId = 0): array
    {
        $query = $this->query()->where('user_id', $userId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderByDesc('is_default')->orderByDesc('created_at')->get()->all();
    }

    /**
     * 用户的默认抬头
     */
    public function getDefaultHeader(int $userId, int $appId = 0): ?InvoiceHeader
    {
        $query = $this->query()->where('user_id', $userId);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->orderByDesc('is_default')->orderByDesc('created_at')->first();
    }

    /**
     * 把该用户其它抬头的默认标记清掉
     */
    public function clearDefaultFlag(int $userId): int
    {
        return (int) $this->query()->where('user_id', $userId)->update(['is_default' => 0]);
    }

    /**
     * 用户的开票记录（含抬头信息）
     */
    public function getUserRecords(int $userId, int $page = 1, int $pageSize = 20, int $status = 0): array
    {
        // 开票记录表本身没有 user_id，需通过发票抬头关联到用户
        $query = InvoiceRecord::query()
            ->join('yxshop_invoice_headers as h', 'invoice_header_id', '=', 'h.id')
            ->where('h.user_id', $userId)
            ->select('yxshop_invoice_records.*');

        if ($status > 0) {
            $query->where('yxshop_invoice_records.status', $status);
        }

        $total = (clone $query)->count();
        $list = $query->orderByDesc('yxshop_invoice_records.created_at')
            ->offset(($page - 1) * $pageSize)
            ->limit($pageSize)
            ->get();

        return ['list' => $list->all(), 'total' => (int) $total];
    }

    /**
     * 订单是否已申请过开票
     */
    public function hasRecordForOrder(int $orderId, int $userId): bool
    {
        return InvoiceRecord::query()
            ->join('yxshop_invoice_headers as h', 'invoice_header_id', '=', 'h.id')
            ->where('order_id', $orderId)
            ->where('h.user_id', $userId)
            ->whereIn('yxshop_invoice_records.status', [InvoiceRecord::STATUS_PENDING, InvoiceRecord::STATUS_ISSUED])
            ->exists();
    }

    /**
     * 创建开票记录
     */
    public function createRecord(array $data): InvoiceRecord
    {
        return InvoiceRecord::create($data);
    }
}
