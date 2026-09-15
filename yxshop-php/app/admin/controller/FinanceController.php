<?php

namespace app\admin\controller;

use support\Request;
use app\service\OrderService;
use app\service\FinanceService;

class FinanceController extends BaseController
{
    protected $orderService;
    protected $financeService;

    public function __construct()
    {
        parent::__construct();
        $this->orderService = new OrderService();
        $this->financeService = new FinanceService();
    }

    // ============================================================
    // 平台财务统计
    // ============================================================

    public function statistics(Request $request)
    {
        $appId = $this->getAppId($request);
        $startDate = trim($request->get('start_date', ''));
        $endDate = trim($request->get('end_date', ''));
        $startTs = $startDate ? strtotime($startDate . ' 00:00:00') : null;
        $endTs = $endDate ? strtotime($endDate . ' 23:59:59') : null;

        $metrics = $this->orderService->getFinanceStatistics($appId, $startTs, $endTs);
        return $this->success($metrics);
    }

    public function transactions(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $status = $request->get('pay_status', '');
        $startDate = trim($request->get('start_date', ''));
        $endDate = trim($request->get('end_date', ''));
        $startTs = $startDate ? strtotime($startDate . ' 00:00:00') : null;
        $endTs = $endDate ? strtotime($endDate . ' 23:59:59') : null;

        $orders = $this->orderService->getTransactionList($appId, $page, $limit, $status, $startTs, $endTs);
        return $this->success($orders);
    }

    // ============================================================
    // 平台对账单
    // ============================================================

    public function statementIndex(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $status = $request->get('status', '');

        $result = $this->financeService->statementIndex($appId, $page, $limit, $status);
        return $this->success($result);
    }

    public function statementShow(Request $request, $id)
    {
        $result = $this->financeService->statementShow((int) $id);
        if (!$result) {
            return $this->errorNotFound('账单不存在');
        }
        return $this->success($result);
    }

    public function statementGenerate(Request $request)
    {
        $appId = $this->getAppId($request);
        $type = $request->post('statement_type', 'daily');
        $startDate = $request->post('start_date', date('Y-m-d', strtotime('-1 day')));
        $endDate = $request->post('end_date', date('Y-m-d', strtotime('-1 day')));

        $operatorId = $this->admin['id'] ?? 0;
        $operatorName = $this->admin['name'] ?? null;

        try {
            $result = $this->financeService->generateStatement($appId, $type, $startDate, $endDate, $operatorId, $operatorName);
            return $this->success($result, '对账单生成成功');
        } catch (\Exception $e) {
            return $this->error('生成失败：' . $e->getMessage());
        }
    }

    public function statementConfirm(Request $request, $id)
    {
        $operatorId = $this->admin['id'] ?? 0;
        $operatorName = $this->admin['name'] ?? null;
        $this->financeService->confirmStatement((int) $id, $operatorId, $operatorName);
        return $this->success(null, '确认成功');
    }

    public function statementExport(Request $request, $id)
    {
        try {
            $result = $this->financeService->exportStatement((int) $id);
            return $this->success($result);
        } catch (\Exception $e) {
            return $this->errorNotFound($e->getMessage());
        }
    }

    // ============================================================
    // 供应商对账单
    // ============================================================

    public function supplierStatementIndex(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $supplierId = (int) $request->get('supplier_id', 0);

        $result = $this->financeService->supplierStatementIndex($appId, $page, $limit, $supplierId);
        return $this->success($result);
    }

    public function supplierStatementGenerate(Request $request)
    {
        $appId = $this->getAppId($request);
        $supplierId = (int) $request->post('supplier_id', 0);
        $startDate = $request->post('start_date', date('Y-m-d', strtotime('-1 month')));
        $endDate = $request->post('end_date', date('Y-m-d', strtotime('-1 day')));

        if ($supplierId <= 0) {
            return $this->error('供应商ID必填');
        }

        try {
            $result = $this->financeService->generateSupplierStatement($appId, $supplierId, $startDate, $endDate);
            return $this->success($result, '供应商账单生成成功');
        } catch (\Exception $e) {
            return $this->error('生成失败：' . $e->getMessage());
        }
    }
}
