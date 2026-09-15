<?php

namespace app\service;

use app\repository\IntegralRepository;
use app\repository\UserRepository;
use app\model\User;
use app\exception\BusinessException;
use Exception;

/**
 * 积分服务
 *
 * 与 UserMoneyService 对称的原子积分账户：
 *   1) lockForUpdate + 原子 increment/decrement（避免读-改-写丢失更新）
 *   2) BaseService::transaction 包裹主表 + 流水表
 *   3) 扣减用 WHERE integral >= amount 守卫，防 TOCTOU 透支
 *
 * @property IntegralRepository $repository
 */
class IntegralService extends BaseService
{
    protected UserRepository $userRepository;

    /** 积分流水类型：1=获得，2=消耗，3=过期 */
    const TYPE_EARN = 1;
    const TYPE_SPEND = 2;
    const TYPE_EXPIRE = 3;

    public function __construct(?IntegralRepository $repository = null)
    {
        parent::__construct($repository ?? new IntegralRepository());
        $this->userRepository = new UserRepository();
    }

    /**
     * 增加积分（原子 + 事务）
     */
    public function addIntegral($userId, $amount, $note = '', $type = self::TYPE_EARN, $orderId = 0)
    {
        $amount = (int) $amount;
        if ($amount <= 0) {
            throw new BusinessException('积分必须大于 0');
        }

        return $this->transaction(function () use ($userId, $amount, $note, $type, $orderId) {
            $user = $this->userRepository->lockForUpdate($userId);
            if (!$user) {
                throw new BusinessException('用户不存在');
            }

            $before = (int) ($user->integral ?? 0);
            $after = $before + $amount;

            $affected = $this->userRepository->incrementIntegral($userId, $amount);
            if ($affected !== 1) {
                throw new BusinessException('积分更新失败');
            }

            $log = $this->writeLog($userId, $user->app_id ?? 0, $amount, $before, $after, $note, $type, $orderId);
            $this->logInfo('积分增加成功', ['user_id' => $userId, 'amount' => $amount]);
            return $log;
        });
    }

    /**
     * 扣减积分（含余额守卫，防透支）
     */
    public function reduceIntegral($userId, $amount, $note = '', $type = self::TYPE_SPEND, $orderId = 0)
    {
        $amount = (int) $amount;
        if ($amount <= 0) {
            throw new BusinessException('积分必须大于 0');
        }

        return $this->transaction(function () use ($userId, $amount, $note, $type, $orderId) {
            $user = $this->userRepository->lockForUpdate($userId);
            if (!$user) {
                throw new BusinessException('用户不存在');
            }

            $before = (int) ($user->integral ?? 0);
            if ($before < $amount) {
                throw new BusinessException('积分不足', 4002);
            }
            $after = $before - $amount;

            // 关键：WHERE integral >= amount 防并发透支
            $affected = $this->userRepository->decrementIntegral($userId, $amount);
            if ($affected !== 1) {
                throw new BusinessException('积分不足', 4002);
            }

            $log = $this->writeLog($userId, $user->app_id ?? 0, -$amount, $before, $after, $note, $type, $orderId);
            $this->logInfo('积分扣减成功', ['user_id' => $userId, 'amount' => $amount]);
            return $log;
        });
    }

    /**
     * 积分流水分页
     */
    public function getIntegralLog($userId, $type = null, $page = 1, $pageSize = 20)
    {
        $typeInt = ($type === null || $type === '') ? null : (int) $type;
        return $this->repository->getPaginatedByUser((int) $userId, $typeInt, (int) $page, (int) $pageSize);
    }

    /**
     * 获取积分余额
     */
    public function getIntegralBalance($userId)
    {
        $user = $this->userRepository->findOrFail($userId);
        return (int) ($user->integral ?? 0);
    }

    /**
     * 积分聚合统计
     */
    public function getIntegralStats($userId)
    {
        return $this->repository->getUserAggregateStats((int) $userId);
    }

    /**
     * 用户月度获得积分
     */
    public function getMonthEarned(int $userId): int
    {
        $monthStart = strtotime(date('Y-m-01') . ' 00:00:00');
        return $this->repository->sumMonthEarnedByUser($userId, $monthStart);
    }

    /**
     * 单用户积分过期
     */
    public function expireIntegral($userId, $days = 365)
    {
        $expireTime = time() - $days * 86400;
        $expiredLogs = $this->repository->getExpiredEarnedLogs((int) $userId, $expireTime);

        if ($expiredLogs->isEmpty()) {
            return 0;
        }

        return $this->transaction(function () use ($userId, $expiredLogs) {
            $totalExpired = 0;
            foreach ($expiredLogs as $log) {
                $log->is_expired = 1;
                $log->save();
                $totalExpired += (int) $log->amount;
            }

            if ($totalExpired > 0) {
                // 原子扣减用户积分余额；余额已不够（曾被消费）则按当前余额扣完
                $balance = $this->getIntegralBalance($userId);
                $deduct = min($balance, $totalExpired);
                if ($deduct > 0) {
                    $this->reduceIntegral($userId, $deduct, '积分过期自动扣除', self::TYPE_EXPIRE, 0);
                }
                $totalExpired = $deduct;
            }

            $this->logInfo('积分过期处理完成', ['user_id' => $userId, 'expired_amount' => $totalExpired]);
            return $totalExpired;
        });
    }

    /**
     * 批量过期
     */
    public function batchExpireIntegral($days = 365)
    {
        $expireTime = time() - $days * 86400;
        $userIds = $this->repository->getUsersWithExpiredLogs($expireTime);

        $totalExpired = 0;
        foreach ($userIds as $userId) {
            $totalExpired += $this->expireIntegral($userId, $days);
        }

        $this->logInfo('批量积分过期处理完成', ['user_count' => count($userIds), 'total_expired' => $totalExpired]);
        return ['user_count' => count($userIds), 'total_expired' => $totalExpired];
    }

    /**
     * 写积分流水
     */
    private function writeLog(int $userId, int $appId, int $change, int $before, int $after, string $note, int $type, int $orderId)
    {
        return $this->repository->create([
            'user_id'        => $userId,
            'app_id'         => $appId,
            'amount'         => $change,
            'before_balance' => $before,
            'after_balance'  => $after,
            'note'           => $note,
            'type'           => $type,
            'order_id'       => $orderId,
            'is_expired'     => 0,
            'created_at'     => time(),
            'updated_at'     => time(),
        ]);
    }
}
