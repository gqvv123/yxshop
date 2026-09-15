<?php

namespace app\controller;

use support\Request;
use support\Response;
use app\service\AfterSalesService;
use app\exception\BusinessException;
use app\exception\NotFoundException;

class AfterSalesController extends BaseController
{
    protected $afterSalesService;

    public function __construct()
    {
        $this->afterSalesService = new AfterSalesService();
    }

    public function list(Request $request): Response
    {
        $status = $request->get('status');
        $type = $request->get('type');
        $page = (int)$request->get('page', 1);
        $limit = (int)$request->get('limit', 20);

        $result = $this->afterSalesService->getList($status, $type, $page, $limit);
        return $this->success($result);
    }

    public function show(Request $request, $id): Response
    {
        $data = $this->afterSalesService->getDetail((int)$id);
        if (!$data) {
            throw new NotFoundException('售后记录不存在');
        }
        return $this->success($data);
    }

    public function process(Request $request, $id): Response
    {
        $data = $request->post();
        $processedBy = $data['processed_by'] ?? null;
        if (!$processedBy) {
            throw new BusinessException('处理人ID不能为空');
        }
        $result = $this->afterSalesService->process((int)$id, $data, (int)$processedBy);
        if (!$result) {
            throw new NotFoundException('售后记录不存在');
        }
        return $this->success(null, '处理成功');
    }

    public function reject(Request $request, $id): Response
    {
        $data = $request->post();
        $processedBy = $data['processed_by'] ?? null;
        if (!$processedBy) {
            throw new BusinessException('处理人ID不能为空');
        }
        $result = $this->afterSalesService->reject((int)$id, $data, (int)$processedBy);
        if (!$result) {
            throw new NotFoundException('售后记录不存在');
        }
        return $this->success(null, '拒绝成功');
    }

    public function statistics(Request $request): Response
    {
        $stats = $this->afterSalesService->getStatistics();
        return $this->success($stats);
    }
}
