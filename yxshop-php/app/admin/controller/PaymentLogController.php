<?php

namespace app\admin\controller;

use support\Request;
use app\service\PaymentLogService;

class PaymentLogController extends BaseController
{
    protected $paymentLogService;

    public function __construct()
    {
        parent::__construct();
        $this->paymentLogService = new PaymentLogService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->paymentLogService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->paymentLogService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->paymentLogService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->paymentLogService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->paymentLogService->delete($id);
        return $this->success(null, '删除成功');
    }
}
