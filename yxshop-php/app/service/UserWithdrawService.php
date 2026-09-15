<?php

namespace app\service;

use app\repository\UserWithdrawRepository;
use app\model\UserWithdraw;
use app\exception\BusinessException;
use Exception;

/**
 * 用户提现服务
 *
 * @property UserWithdrawRepository $repository
 */
class UserWithdrawService extends BaseService
{
    private UserMoneyService $moneyService;

    public function __construct(?UserWithdrawRepository $repository = null)
    {
        parent::__construct($repository ?? new UserWithdrawRepository());
        $this->moneyService = new UserMoneyService();
    }

    /**
     * 申请提现
     */
    public function applyWithdraw(int $userId, float $amount, int $withdrawType, string $account, string $accountName, string $bankName = '', string $bankBranch = ''): UserWithdraw
    {
        try {
            $this->logInfo('提现申请开始', [
                'user_id' => $userId,
                'amount' => $amount,
                'withdraw_type' => $withdrawType,
            ]);

            if ($amount <= 0) {
                throw new BusinessException('提现金额必须大于0');
            }
            if ($amount < 10) {
                throw new BusinessException('最低提现金额为10元');
            }
            if (empty($account)) {
                throw new BusinessException('提现账号不能为空');
            }
            if ($withdrawType == UserWithdraw::TYPE_BANK && empty($accountName)) {
                throw new BusinessException('银行开户人姓名不能为空');
            }

            // 获取用户app_id
            $userService = new UserService();
            $user = $userService->findOrFail($userId);
            $appId = (int) ($user->app_id ?? 0);

            // 校验余额
            $balance = $this->moneyService->getBalance($userId);
            if ($balance['available'] < $amount) {
                throw new BusinessException('可用余额不足，当前可用：' . $balance['available'] . '元');
            }

            // 检查是否有待审核的提现
            $pendingCount = $this->repository->countPendingByUser($userId);
            if ($pendingCount > 0) {
                throw new BusinessException('您有正在审核的提现申请，请等待处理完成');
            }

            $fee = 0;
            $actualAmount = $amount - $fee;

            // 冻结提现金额
            $this->moneyService->reduceMoney($userId, $amount, '提现申请冻结');

            // 创建提现记录
            $withdraw = $this->repository->create([
                'user_id'       => $userId,
                'amount'        => $amount,
                'fee'           => $fee,
                'actual_amount' => $actualAmount,
                'withdraw_type' => $withdrawType,
                'account'       => $account,
                'account_name'  => $accountName,
                'bank_name'     => $bankName,
                'bank_branch'   => $bankBranch,
                'status'        => UserWithdraw::STATUS_PENDING,
                'app_id'        => $appId,
                'created_at'    => time(),
                'updated_at'    => time(),
            ]);

            $this->logInfo('提现申请成功', ['withdraw_id' => $withdraw->id]);
            return $withdraw;
        } catch (Exception $e) {
            $this->logError('提现申请失败', [
                'user_id' => $userId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户提现记录（分页）
     */
    public function getRecordsByUser(int $userId, int $page = 1, int $pageSize = 20)
    {
        return $this->repository->getByUserPaginated($userId, $page, $pageSize);
    }

    /**
     * 获取后台分页列表
     */
    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        return $this->repository->getPaginatedList($appId, $filters, $pageSize);
    }
}
