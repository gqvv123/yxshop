<?php

namespace app\api\v1\controller;

use support\Request;
use app\service\UserContractService;

class ContractController extends BaseController
{
    protected $contractService;

    public function __construct()
    {
        $this->contractService = new UserContractService();
    }

    /**
     * 获取当前用户的合同列表
     *
     * GET /api/v1/contract/my-list?page=1&page_size=20
     */
    public function myList(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $page = max(1, (int) $request->get('page', 1));
        $pageSize = min(50, max(1, (int) $request->get('page_size', 20)));

        $result = $this->contractService->getMyList($userId, $page, $pageSize);

        return $this->success($result);
    }

    /**
     * 获取合同详情
     *
     * GET /api/v1/contract/detail?id=1
     */
    public function detail(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $id = (int) $request->get('id', 0);
        if ($id <= 0) {
            return $this->error('参数错误');
        }

        $result = $this->contractService->getDetail($id, $userId);
        if (!$result) {
            return $this->error('合同不存在');
        }

        return $this->success($result);
    }

    /**
     * 获取合同统计数量
     *
     * GET /api/v1/contract/stats
     */
    public function stats(Request $request)
    {
        $userId = $this->getCurrentUserId($request);
        if (!$userId) {
            return $this->error('用户未登录');
        }

        $stats = $this->contractService->getStats($userId);

        return $this->success($stats);
    }
}
