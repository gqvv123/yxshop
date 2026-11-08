<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\UserWithdrawService;
use app\service\UserMoneyService;
use app\service\UserService;
use app\model\UserWithdraw;

class WithdrawController extends BaseController
{
    protected $withdrawService;
    protected $moneyService;
    protected $userService;

    public function __construct()
    {
        $this->withdrawService = new UserWithdrawService();
        $this->moneyService = new UserMoneyService();
        $this->userService = new UserService();
    }

    /**
     * 提现申请
     *
     * POST /api/v1/withdraw/apply
     * amount=100, withdraw_type=1, account=xxx, account_name=xxx, bank_name=xxx
     */
    public function apply(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $amount = (float) $request->post('amount', 0);
        $withdrawType = (int) $request->post('withdraw_type', UserWithdraw::TYPE_WECHAT);
        $account = trim($request->post('account', ''));
        $accountName = trim($request->post('account_name', ''));
        $bankName = trim($request->post('bank_name', ''));
        $bankBranch = trim($request->post('bank_branch', ''));

        if ($amount <= 0) {
            return $this->error('提现金额必须大于0');
        }

        if ($amount < 10) {
            return $this->error('最低提现金额为10元');
        }

        if (empty($account)) {
            return $this->error('提现账号不能为空');
        }

        if ($withdrawType == UserWithdraw::TYPE_BANK && empty($accountName)) {
            return $this->error('银行开户人姓名不能为空');
        }

        try {
            $withdraw = $this->withdrawService->applyWithdraw(
                $userId,
                $amount,
                $withdrawType,
                $account,
                $accountName,
                $bankName,
                $bankBranch
            );

            return $this->success([
                'id'             => $withdraw->id,
                'amount'         => $amount,
                'fee'            => $withdraw->fee,
                'actual_amount'  => $withdraw->actual_amount,
                'status'         => UserWithdraw::STATUS_PENDING,
                'status_text'    => '审核中',
            ], '提现申请已提交，等待审核');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 提现记录列表
     *
     * GET /api/v1/withdraw/records?page=1&page_size=20
     */
    public function records(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 20)));

        $result = $this->withdrawService->getRecordsByUser($userId, $page, $pageSize);

        $list = collect($result->items())->map(function ($w) {
            return [
                'id'             => $w->id,
                'amount'         => (float) $w->amount,
                'fee'            => (float) $w->fee,
                'actual_amount'  => (float) $w->actual_amount,
                'withdraw_type'  => $w->withdraw_type,
                'type_text'      => [
                    UserWithdraw::TYPE_BANK   => '银行卡',
                    UserWithdraw::TYPE_ALIPAY => '支付宝',
                    UserWithdraw::TYPE_WECHAT => '微信',
                ][$w->withdraw_type] ?? '未知',
                'status'         => $w->status,
                'status_text'    => $w->status_text,
                'remark'         => $w->remark ?? '',
                'process_desc'   => $w->process_desc ?? '',
                'process_time'   => $w->process_time ?? 0,
                'created_at'     => $w->created_at,
            ];
        })->values();

        return $this->success([
            'list'      => $list,
            'total'     => $result->total(),
            'page'      => $result->currentPage(),
            'page_size' => $result->perPage(),
            'last_page' => $result->lastPage(),
        ]);
    }
}
