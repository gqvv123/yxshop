<?php

namespace app\repository;

use app\model\AiCreditPackage;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * AI 算力包仓储
 */
class AiCreditPackageRepository extends BaseRepository
{
    public function __construct(?AiCreditPackage $model = null)
    {
        parent::__construct($model ?? new AiCreditPackage());
    }

    /**
     * 按应用分页查询算力包
     */
    public function getListByApp(int $appId, int $pageSize = 20, array $filters = []): LengthAwarePaginator
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('status', (int) $filters['status']);
        }
        if (isset($filters['payment_status']) && $filters['payment_status'] !== '') {
            $query->where('payment_status', (int) $filters['payment_status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($pageSize);
    }

    /**
     * 获取应用下有效的算力包列表
     */
    public function getActivePackages(int $appId)
    {
        return $this->query()
            ->where('app_id', $appId)
            ->where('status', AiCreditPackage::STATUS_ACTIVE)
            ->where('payment_status', AiCreditPackage::PAYMENT_PAID)
            ->where('remaining_calls', '>', 0)
            ->get();
    }

    /**
     * 获取应用剩余总算力次数
     */
    public function getTotalRemainingCalls(int $appId): int
    {
        $packages = $this->getActivePackages($appId);
        $total = 0;
        foreach ($packages as $pkg) {
            if ($pkg->isUsable()) {
                $total += $pkg->remaining_calls;
            }
        }
        return $total;
    }

    /**
     * 扣减算力（从最早过期的有效算力包开始扣）
     */
    public function deductCalls(int $appId, int $calls = 1): bool
    {
        $packages = $this->getActivePackages($appId);
        $remaining = $calls;

        foreach ($packages as $pkg) {
            if ($remaining <= 0) {
                break;
            }
            if (!$pkg->isUsable()) {
                continue;
            }

            $deduct = min($pkg->remaining_calls, $remaining);
            $pkg->remaining_calls -= $deduct;
            $pkg->used_calls += $deduct;

            if ($pkg->remaining_calls <= 0) {
                $pkg->status = AiCreditPackage::STATUS_EXHAUSTED;
            }
            $pkg->save();
            $remaining -= $deduct;
        }

        return $remaining <= 0;
    }
}
