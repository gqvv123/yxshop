<?php

namespace app\service;

use app\model\User;
use app\model\UserMoneyLog;
use app\repository\UserRepository;
use app\repository\UserMoneyLogRepository;
use app\exception\BusinessException;
use support\Db;
use Exception;

/**
 * 用户余额服务
 *
 * 所有余额变更必须走本服务，保证：
 *   1) 原子 increment/decrement（避免读-改-写丢失更新）
 *   2) Db::transaction 包裹主表 + 流水表
 *   3) 扣款用 WHERE money >= amount 守卫，防 TOCTOU 透支
 *
 * 4 层架构：所有数据访问通过对应 Repository
 */
class UserMoneyService extends BaseService
{
    protected UserRepository $userRepository;

    public function __construct()
    {
        parent::__construct(new UserMoneyLogRepository());
        $this->userRepository = new UserRepository();
    }

    /**
     * 增加余额
     *
     * @param int $userId
     * @param float|string $amount  正数
     * @param string $note
     * @param int $type   1=收入 2=支出（此处固定 1）
     * @param int $orderId
     * @return User 更新后的用户实例
     * @throws Exception
     */
    public function addMoney(int $userId, $amount, string $note = '', int $type = UserMoneyLog::TYPE_INCOME, int $orderId = 0): User
    {
        $amount = (float) $amount;
        if ($amount <= 0) {
            throw new Exception('金额必须大于 0');
        }

        return $this->transaction(function () use ($userId, $amount, $note, $type, $orderId) {
            // 行锁 + 原子自增
            $user = $this->userRepository->query()->where('id', $userId)->lockForUpdate()->first();
            if (!$user) {
                throw new Exception('用户不存在');
            }

            $before = (float) $user->money;
            $after = $before + $amount;

            // 原子写入，避免覆盖并发改动
            $affected = $this->userRepository->query()->where('id', $userId)->increment('money', $amount);
            if ($affected !== 1) {
                throw new Exception('余额更新失败');
            }

            $this->writeLog($user, $amount, $before, $after, $note, $type, $orderId);

            $user->money = $after;
            return $user;
        });
    }

    /**
     * 减少余额（带余额守卫，防透支）
     *
     * @param int $userId
     * @param float|string $amount  正数
     * @param string $note
     * @param int $orderId
     * @return User
     * @throws Exception 余额不足时抛 BusinessException
     */
    public function reduceMoney(int $userId, $amount, string $note = '', int $orderId = 0): User
    {
        $amount = (float) $amount;
        if ($amount <= 0) {
            throw new Exception('金额必须大于 0');
        }

        return $this->transaction(function () use ($userId, $amount, $note, $orderId) {
            $user = $this->userRepository->query()->where('id', $userId)->lockForUpdate()->first();
            if (!$user) {
                throw new Exception('用户不存在');
            }

            $before = (float) $user->money;
            if ($before < $amount) {
                throw new BusinessException('余额不足', 4001);
            }

            $after = $before - $amount;

            // 关键：WHERE money >= amount 防止并发透支
            $affected = $this->userRepository->query()
                ->where('id', $userId)
                ->where('money', '>=', $amount)
                ->decrement('money', $amount);
            if ($affected !== 1) {
                throw new BusinessException('余额不足', 4001);
            }

            $this->writeLog($user, -$amount, $before, $after, $note, UserMoneyLog::TYPE_EXPENSE, $orderId);

            $user->money = $after;
            return $user;
        });
    }

    /**
     * 写余额流水
     */
    private function writeLog(User $user, float $change, float $before, float $after, string $note, int $type, int $orderId): void
    {
        $this->repository->create([
            'user_id' => $user->id,
            'app_id' => $user->app_id ?? 0,
            'money' => $change,
            'before_money' => $before,
            'after_money' => $after,
            'note' => $note,
            'type' => $type,
            'order_id' => $orderId,
            'created_at' => time(),
            'updated_at' => time(),
        ]);
    }

    /**
     * 查询余额（含冻结）
     */
    public function getBalance(int $userId): array
    {
        $user = $this->userRepository->findOrFail($userId);
        return [
            'money' => (float) $user->money,
            'freeze_money' => (float) ($user->freeze_money ?? 0),
            'available' => (float) $user->money - (float) ($user->freeze_money ?? 0),
        ];
    }

    /**
     * 获取用户余额流水（分页）
     */
    public function getMoneyLogs(int $userId, ?int $type = null, int $page = 1, int $pageSize = 20)
    {
        $query = $this->repository->query()->where('user_id', $userId);
        if ($type !== null && $type !== '') {
            $query->where('type', (int) $type);
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

        $balance = $this->getBalance($userId);

        return [
            'balance'       => $balance['money'],
            'freeze'        => $balance['freeze_money'],
            'available'     => $balance['available'],
            'month_income'  => round(abs((float) $monthIncome), 2),
            'month_expense' => round(abs((float) $monthExpense), 2),
            'total_income'  => round(abs((float) $totalIncome), 2),
            'total_expense' => round(abs((float) $totalExpense), 2),
        ];
    }
}
