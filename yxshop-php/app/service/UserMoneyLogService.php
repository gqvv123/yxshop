<?php

namespace app\service;

use app\repository\UserMoneyLogRepository;
use app\model\UserMoneyLog;
use Exception;

class UserMoneyLogService extends BaseService
{
    public function __construct(?UserMoneyLogRepository $repository = null)
    {
        parent::__construct($repository ?? new UserMoneyLogRepository());
    }

    public function getList($appId = 0, $pageSize = 20, $keyword = '')
    {
        return $this->repository->getList($appId, $pageSize, $keyword);
    }

    public function getDetail($id)
    {
        return $this->repository->find($id);
    }

    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    public function delete($id)
    {
        return $this->repository->softDelete($id);
    }

    /**
     * 获取用户余额流水（分页）
     */
    public function getByUserPaginated(int $userId, ?int $type = null, int $page = 1, int $pageSize = 20)
    {
        $query = $this->repository->query()->where('user_id', $userId);
        if ($type !== null) {
            $query->where('type', $type);
        }
        return $query->orderBy('created_at', 'desc')
            ->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * 获取用户收支统计
     */
    public function getMoneyStats(int $userId): array
    {
        $monthStart = strtotime(date('Y-m-01') . ' 00:00:00');

        $monthIncome = $this->repository->query()
            ->where('user_id', $userId)
            ->where('type', UserMoneyLog::TYPE_INCOME)
            ->where('created_at', '>=', $monthStart)
            ->sum('money');

        $monthExpense = $this->repository->query()
            ->where('user_id', $userId)
            ->where('type', UserMoneyLog::TYPE_EXPENSE)
            ->where('created_at', '>=', $monthStart)
            ->sum('money');

        $totalIncome = $this->repository->query()
            ->where('user_id', $userId)
            ->where('type', UserMoneyLog::TYPE_INCOME)
            ->sum('money');

        $totalExpense = $this->repository->query()
            ->where('user_id', $userId)
            ->where('type', UserMoneyLog::TYPE_EXPENSE)
            ->sum('money');

        return [
            'month_income'  => round(abs((float) $monthIncome), 2),
            'month_expense' => round(abs((float) $monthExpense), 2),
            'total_income'  => round(abs((float) $totalIncome), 2),
            'total_expense' => round(abs((float) $totalExpense), 2),
        ];
    }
}
