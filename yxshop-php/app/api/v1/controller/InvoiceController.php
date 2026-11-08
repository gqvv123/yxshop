<?php

namespace app\api\v1\controller;

use app\service\InvoiceService;
use support\Request;

/**
 * C 端控制器：发票。
 *
 * 对应管理端「财务管理 → 发票管理」的用户侧能力。
 */
class InvoiceController extends BaseController
{
    protected InvoiceService $invoiceService;

    public function __construct()
    {
        $this->invoiceService = new InvoiceService();
    }

    /**
     * 我的发票抬头列表
     *
     * GET /api/v1/invoice/headers
     */
    public function getHeaders(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('请先登录', 401);
        }
        $appId = (int) $request->get('app_id', 0);
        $list = $this->invoiceService->getHeaderList($userId, $appId);

        return $this->success(['list' => $list, 'total' => count($list)]);
    }

    /**
     * 保存发票抬头（新增 / 编辑）
     *
     * POST /api/v1/invoice/header
     */
    public function saveHeader(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('请先登录', 401);
        }

        try {
            $header = $this->invoiceService->saveHeader(
                $userId,
                $request->post(),
                (int) $request->get('app_id', 0)
            );
            return $this->success($header, '保存成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除发票抬头
     *
     * DELETE /api/v1/invoice/header/{id}
     */
    public function deleteHeader(Request $request, $id)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('请先登录', 401);
        }

        try {
            $this->invoiceService->deleteHeader($userId, (int) $id);
            return $this->success(null, '删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 申请开票
     *
     * POST /api/v1/invoice/apply
     */
    public function apply(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('请先登录', 401);
        }

        try {
            $record = $this->invoiceService->apply(
                $userId,
                $request->post(),
                (int) $request->get('app_id', 0)
            );
            return $this->success($record, '开票申请已提交');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 我的开票记录
     *
     * GET /api/v1/invoice/records?page=1&page_size=20&status=1
     */
    public function getRecords(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('请先登录', 401);
        }

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(100, max(1, (int) $request->get('page_size', 20)));
        $status = (int) $request->get('status', 0);

        $result = $this->invoiceService->getRecordList($userId, $page, $pageSize, $status);

        return $this->success([
            'list'      => $result['list'],
            'total'     => $result['total'],
            'page'      => $page,
            'page_size' => $pageSize,
        ]);
    }
}
