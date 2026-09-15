<?php

namespace app\repository;

use app\model\AfterSales;

/**
 * 售后申请仓储
 *
 * 4 层架构：Repository 只负责数据访问（查询、乐观锁更新），
 * 状态校验、事务编排、日志写入由 AfterSalesService 负责。
 */
class AfterSalesRepository extends BaseRepository
{
    protected $model = AfterSales::class;

    /**
     * 获取详情（含操作日志 + 退款记录）
     */
    public function getDetail(int $id): ?AfterSales
    {
        return $this->query()
            ->with(['logs.actor', 'refundRecords', 'user', 'order'])
            ->find($id);
    }

    /**
     * 后台分页列表（按 status / sub_type 筛选）
     */
    public function getPaginatedList(?int $status = null, ?int $subType = null, int $page = 1, int $limit = 20, int $appId = 0): array
    {
        $base = $this->query();
        if ($status !== null) {
            $base->where('status', $status);
        }
        if ($subType !== null) {
            $base->where('sub_type', $subType);
        }
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        $total = (clone $base)->count();
        $records = $base->orderBy('created_at', 'desc')
            ->offset(($page - 1) * $limit)
            ->limit($limit)
            ->get()
            ->toArray();

        return ['list' => $records, 'total' => $total, 'page' => $page, 'limit' => $limit];
    }

    /**
     * 乐观锁更新状态（不加事务，由 Service 层包裹事务）
     *
     * @param int $id           记录ID
     * @param int $currentVersion 当前版本号（乐观锁）
     * @param array $data      更新数据
     * @return int 受影响行数（0 表示并发冲突）
     */
    public function updateWithVersionLock(int $id, int $currentVersion, array $data): int
    {
        $data['version'] = ($currentVersion + 1);
        $data['updated_at'] = $data['updated_at'] ?? time();

        return $this->query()
            ->where('id', $id)
            ->where('version', $currentVersion)
            ->update($data);
    }

    // ============================================================
    // 查询方法
    // ============================================================

    public function getUserAfterSales($userId, $status = null, $appId = 0)
    {
        $query = $this->query()
            ->where('user_id', $userId)
            ->with(['order', 'orderItem'])
            ->orderBy('created_at', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->get();
    }

    public function getPendingAfterSales($appId = 0)
    {
        $query = $this->query()
            ->where('status', AfterSales::STATUS_PENDING)
            ->with(['user', 'order'])
            ->orderBy('created_at', 'asc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->get();
    }

    public function getAfterSalesStats($appId = 0)
    {
        $base = $this->query();
        if ($appId > 0) {
            $base->where('app_id', $appId);
        }

        return [
            'total'     => (clone $base)->count(),
            'pending'   => (clone $base)->where('status', AfterSales::STATUS_PENDING)->count(),
            'approved'  => (clone $base)->where('status', AfterSales::STATUS_APPROVED)->count(),
            'completed' => (clone $base)->where('status', AfterSales::STATUS_COMPLETED)->count(),
        ];
    }

    public function getAfterSalesTypeStats($appId = 0)
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->selectRaw('sub_type AS type, COUNT(*) AS count')
            ->groupBy('sub_type')
            ->get();
    }

    public function searchAfterSales($keyword, $appId = 0)
    {
        $escaped = str_replace(['%', '_'], ['\\%', '\\_'], (string) $keyword);
        $query = $this->query()
            ->where(function ($q) use ($escaped) {
                $q->where('reasons', 'like', '%' . $escaped . '%')
                  ->orWhere('content', 'like', '%' . $escaped . '%')
                  ->orWhereHas('user', function ($u) use ($escaped) {
                      $u->where('nickname', 'like', '%' . $escaped . '%');
                  });
            })
            ->with(['user', 'order'])
            ->orderBy('created_at', 'desc');

        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->get();
    }

    public function getStatusCounts(int $appId = 0): array
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    public function getTypeCounts(int $appId = 0): array
    {
        $query = $this->query();
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->selectRaw('sub_type AS type, COUNT(*) AS count')
            ->groupBy('sub_type')
            ->get()
            ->toArray();
    }

    public function countToday(int $appId = 0): int
    {
        $query = $this->query()->where('created_at', '>=', strtotime(date('Y-m-d')));
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->count();
    }

    public function countPending(int $appId = 0): int
    {
        $query = $this->query()->where('status', AfterSales::STATUS_PENDING);
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->count();
    }

    public function getAllByApp(int $appId = 0)
    {
        $query = $this->query()->orderBy('created_at', 'desc');
        if ($appId > 0) {
            $query->where('app_id', $appId);
        }
        return $query->get();
    }

    public function batchUpdateByIds(array $ids, array $data): int
    {
        if (empty($ids)) {
            return 0;
        }
        $data['updated_at'] = $data['updated_at'] ?? time();
        return $this->updateWhere(['id' => $ids], $data);
    }
}
