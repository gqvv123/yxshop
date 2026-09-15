<?php

namespace app\service;

use app\repository\AccountStatementRepository;
use app\repository\AccountStatementItemRepository;
use app\repository\SupplierStatementRepository;
use app\repository\OrderRepository;
use app\repository\PurchaseOrderRepository;
use app\model\AccountStatement;
use app\model\AccountStatementItem;
use app\model\SupplierStatement;
use Exception;

/**
 * 财务服务
 *
 * 4 层架构：所有数据访问通过对应 Repository
 * 对账单生成使用 Db::transaction 包裹多表写入。
 */
class FinanceService extends BaseService
{
    protected AccountStatementRepository $statementRepository;
    protected AccountStatementItemRepository $itemRepository;
    protected SupplierStatementRepository $supplierStatementRepository;
    protected OrderRepository $orderRepository;
    protected PurchaseOrderRepository $purchaseOrderRepository;

    public function __construct()
    {
        parent::__construct(new AccountStatementRepository());
        $this->statementRepository = new AccountStatementRepository();
        $this->itemRepository = new AccountStatementItemRepository();
        $this->supplierStatementRepository = new SupplierStatementRepository();
        $this->orderRepository = new OrderRepository();
        $this->purchaseOrderRepository = new PurchaseOrderRepository();
    }

    // ============================================================
    // 平台财务统计（委托 OrderService 已有方法）
    // ============================================================

    /**
     * 平台对账单列表
     */
    public function statementIndex(int $appId, int $page = 1, int $limit = 20, string $status = '')
    {
        $paginator = $this->statementRepository->getPaginatedList($appId, $limit, $status);
        return [
            'list' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * 对账单详情
     */
    public function statementShow(int $id)
    {
        $statement = $this->statementRepository->findById($id);
        if (!$statement) {
            return null;
        }
        $items = $this->itemRepository->getByStatementId($id);
        return ['statement' => $statement, 'items' => $items];
    }

    /**
     * 生成对账单
     */
    public function generateStatement(int $appId, string $type, string $startDate, string $endDate, int $operatorId, ?string $operatorName): array
    {
        $startTs = strtotime($startDate . ' 00:00:00');
        $endTs = strtotime($endDate . ' 23:59:59');
        $statementNo = 'PS' . date('YmdHis') . random_int(1000, 9999);

        return $this->transaction(function () use ($appId, $type, $startTs, $endTs, $operatorId, $operatorName, $statementNo) {
            // 统计订单数据
            $orderStats = $this->orderRepository->query()
                ->where('app_id', $appId)
                ->whereBetween('created_at', [$startTs, $endTs])
                ->selectRaw('COUNT(*) as total_orders, SUM(total_price) as total_amount, SUM(refund_amount ?? 0) as total_refund')
                ->first();

            $totalOrders = $orderStats->total_orders ?? 0;
            $totalAmount = $orderStats->total_amount ?? 0;
            $totalRefund = $orderStats->total_refund ?? 0;
            $platformFee = round($totalAmount * 0.05, 2);
            $settleAmount = $totalAmount - $totalRefund - $platformFee;

            $statement = $this->statementRepository->create([
                'statement_no'   => $statementNo,
                'statement_type' => $type,
                'start_date'     => $startTs,
                'end_date'       => $endTs,
                'total_orders'  => $totalOrders,
                'total_amount'  => $totalAmount,
                'total_refund'  => $totalRefund,
                'platform_fee'  => $platformFee,
                'settle_amount' => $settleAmount,
                'status'        => 0,
                'operator_id'   => $operatorId,
                'operator_name' => $operatorName,
                'app_id'        => $appId,
                'created_at'    => time(),
                'updated_at'    => time(),
            ]);

            // 批量插入明细
            $orders = $this->orderRepository->query()
                ->where('app_id', $appId)
                ->whereBetween('created_at', [$startTs, $endTs])
                ->get();

            $items = [];
            foreach ($orders as $order) {
                $items[] = [
                    'statement_id' => $statement->id,
                    'order_id'    => $order->id,
                    'order_no'    => $order->order_no ?? '',
                    'user_id'     => $order->user_id ?? 0,
                    'order_amount' => $order->total_price ?? 0,
                    'refund_amount' => $order->refund_amount ?? 0,
                    'commission'  => round(($order->total_price ?? 0) * 0.05, 2),
                    'item_type'   => 'order',
                    'app_id'     => $appId,
                    'created_at' => time(),
                ];
            }

            if (!empty($items)) {
                $this->itemRepository->batchCreate($items);
            }

            return ['id' => $statement->id, 'statement_no' => $statementNo];
        });
    }

    /**
     * 确认对账单
     */
    public function confirmStatement(int $id, int $operatorId, ?string $operatorName): void
    {
        $this->statementRepository->confirm($id, $operatorId, $operatorName);
    }

    /**
     * 导出对账单
     */
    public function exportStatement(int $id): array
    {
        $statement = $this->statementRepository->findById($id);
        if (!$statement) {
            throw new Exception('对账单不存在');
        }
        $items = $this->itemRepository->getByStatementId($id);
        $csv = "订单号,用户ID,订单金额,退款金额,平台佣金,类型\n";
        foreach ($items as $item) {
            $csv .= "{$item->order_no},{$item->user_id},{$item->order_amount},{$item->refund_amount},{$item->commission},{$item->item_type}\n";
        }
        return ['csv' => $csv, 'statement_no' => $statement->statement_no];
    }

    // ============================================================
    // 供应商对账单
    // ============================================================

    /**
     * 供应商对账单列表
     */
    public function supplierStatementIndex(int $appId, int $page = 1, int $limit = 20, int $supplierId = 0)
    {
        $paginator = $this->supplierStatementRepository->getPaginatedList($appId, $limit, $supplierId);
        return [
            'list' => $paginator->items(),
            'total' => $paginator->total(),
            'page' => $page,
            'limit' => $limit,
        ];
    }

    /**
     * 生成供应商对账单
     */
    public function generateSupplierStatement(int $appId, int $supplierId, string $startDate, string $endDate): array
    {
        $startTs = strtotime($startDate . ' 00:00:00');
        $endTs = strtotime($endDate . ' 23:59:59');
        $statementNo = 'SS' . date('YmdHis') . random_int(1000, 9999);

        return $this->transaction(function () use ($appId, $supplierId, $startTs, $endTs, $statementNo) {
            // 统计采购订单数据
            $stats = $this->purchaseOrderRepository->getStats($appId, $supplierId, $startTs, $endTs);

            $totalSales = $stats['total_sales'];
            $commission = round($totalSales * 0.02, 2);
            $settleAmount = $totalSales - $commission;

            $supplierStatement = $this->supplierStatementRepository->create([
                'statement_no'  => $statementNo,
                'supplier_id'  => $supplierId,
                'start_date'   => $startTs,
                'end_date'    => $endTs,
                'total_orders' => $stats['total_orders'],
                'total_sales'  => $totalSales,
                'commission'   => $commission,
                'settle_amount' => $settleAmount,
                'status'       => 0,
                'app_id'       => $appId,
                'created_at'   => time(),
                'updated_at'   => time(),
            ]);

            return ['id' => $supplierStatement->id, 'statement_no' => $statementNo];
        });
    }
}
