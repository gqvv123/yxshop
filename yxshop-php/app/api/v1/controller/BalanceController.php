<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\UserMoneyService;
use app\service\UserMoneyLogService;
use app\service\RechargePackageService;
use app\service\RechargeOrderService;
use app\service\UserService;
use app\model\UserMoneyLog;
use app\model\RechargeOrder;
use app\exception\BusinessException;

class BalanceController extends BaseController
{
    protected $moneyService;
    protected $moneyLogService;
    protected $packageService;
    protected $orderService;
    protected $userService;

    public function __construct()
    {
        $this->moneyService = new UserMoneyService();
        $this->moneyLogService = new UserMoneyLogService();
        $this->packageService = new RechargePackageService();
        $this->orderService = new RechargeOrderService();
        $this->userService = new UserService();
    }

    /**
     * 余额信息
     *
     * GET /api/v1/user/balance
     * 返回：余额、冻结金额、可用余额
     */
    public function balance(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $result = $this->moneyService->getBalance((int) $userId);
        return $this->success($result);
    }

    /**
     * 余额流水
     *
     * GET /api/v1/user/money-log?type=1&page=1&page_size=20
     */
    public function moneyLog(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $type = $request->get('type');
        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 20)));

        $typeInt = ($type !== null && $type !== '') ? (int) $type : null;
        $result = $this->moneyLogService->getByUserPaginated($userId, $typeInt, $page, $pageSize);

        $items = collect($result->items())->map(function ($log) {
            return [
                'id'            => $log->id,
                'money'         => (float) $log->money,
                'before_money'  => (float) $log->before_money,
                'after_money'   => (float) $log->after_money,
                'type'          => $log->type,
                'type_text'     => $log->type == UserMoneyLog::TYPE_INCOME ? '收入' : '支出',
                'note'          => $log->note ?? '',
                'order_id'      => $log->order_id ?? 0,
                'created_at'    => $log->created_at,
            ];
        })->values();

        return $this->success([
            'list'      => $items,
            'total'     => $result->total(),
            'page'      => $result->currentPage(),
            'page_size' => $result->perPage(),
            'last_page' => $result->lastPage(),
        ]);
    }

    /**
     * 收支统计
     *
     * GET /api/v1/user/money-stats
     */
    public function moneyStats(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $stats = $this->moneyLogService->getMoneyStats($userId);
        $balance = $this->moneyService->getBalance((int) $userId);

        return $this->success([
            'balance'       => $balance['money'],
            'freeze'        => $balance['freeze_money'],
            'available'     => $balance['available'],
            'month_income'  => $stats['month_income'],
            'month_expense' => $stats['month_expense'],
            'total_income'  => $stats['total_income'],
            'total_expense' => $stats['total_expense'],
        ]);
    }

    /**
     * 充值套餐列表
     *
     * GET /api/v1/recharge/packages
     */
    public function packages(Request $request)
    {
        $appId = (int) $request->get('app_id', 0);

        try {
            $packages = $this->packageService->getEnabled($appId);

            $list = $packages->map(function ($pkg) {
                return [
                    'id'             => $pkg->id,
                    'package_name'   => $pkg->package_name,
                    'recharge_amount'=> (float) $pkg->recharge_amount,
                    'bonus_amount'   => (float) $pkg->bonus_amount,
                    'bonus_points'   => (int) $pkg->bonus_points,
                    'actual_amount'  => (float) ($pkg->recharge_amount + $pkg->bonus_amount),
                    'sort'           => $pkg->sort,
                ];
            });

            return $this->success($list);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 创建充值订单
     *
     * POST /api/v1/recharge/create
     * package_id=1
     */
    public function create(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $packageId = (int) $request->post('package_id', 0);
        if ($packageId <= 0) {
            return $this->error('请选择充值套餐');
        }

        try {
            $user = $this->userService->findOrFail($userId);
            $package = $this->packageService->getEnabled((int) ($user->app_id ?? 0))
                ->where('id', $packageId)
                ->first();

            if (!$package) {
                throw new BusinessException('充值套餐不存在或已下架');
            }

            $orderNo = 'RC' . date('YmdHis') . str_pad((string) mt_rand(0, 9999), 4, '0', STR_PAD_LEFT);

            $order = $this->orderService->create([
                'order_no'       => $orderNo,
                'user_id'        => $userId,
                'package_id'     => $packageId,
                'recharge_amount'=> $package->recharge_amount,
                'bonus_amount'   => $package->bonus_amount,
                'bonus_points'   => $package->bonus_points,
                'pay_amount'     => $package->recharge_amount,
                'pay_method'     => '',
                'pay_status'     => RechargeOrder::PAY_STATUS_UNPAID,
                'status'         => RechargeOrder::STATUS_PENDING,
                'app_id'         => $user->app_id ?? 0,
            ]);

            return $this->success([
                'order_id'       => $order->id,
                'order_no'       => $orderNo,
                'pay_amount'     => (float) $order->pay_amount,
                'recharge_amount'=> (float) $order->recharge_amount,
                'bonus_amount'   => (float) $order->bonus_amount,
                'bonus_points'   => (int) $order->bonus_points,
            ], '订单创建成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 充值记录
     *
     * GET /api/v1/recharge/records?page=1&page_size=20
     */
    public function records(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 20)));

        $user = $this->userService->findOrFail($userId);
        $orders = $this->orderService->getByUser((int) $userId, (int) ($user->app_id ?? 0));

        // 手动分页
        $total = $orders->count();
        $offset = ($page - 1) * $pageSize;
        $list = $orders->slice($offset, $pageSize)->map(function ($order) {
            return [
                'id'             => $order->id,
                'order_no'       => $order->order_no,
                'recharge_amount'=> (float) $order->recharge_amount,
                'bonus_amount'   => (float) $order->bonus_amount,
                'pay_amount'     => (float) $order->pay_amount,
                'bonus_points'   => (int) $order->bonus_points,
                'pay_status'     => $order->pay_status,
                'pay_status_text'=> $order->pay_status == RechargeOrder::PAY_STATUS_PAID ? '已支付' : '未支付',
                'status'         => $order->status,
                'pay_method'     => $order->pay_method ?? '',
                'created_at'     => $order->created_at,
            ];
        })->values();

        return $this->success([
            'list'      => $list,
            'total'     => $total,
            'page'      => $page,
            'page_size' => $pageSize,
        ]);
    }
}
