<?php

namespace app\admin\controller;

use support\Request;
use app\service\OrderDeliveryService;

class OrderDeliveryController extends BaseController
{
    protected $orderDeliveryService;

    public function __construct()
    {
        parent::__construct();
        $this->orderDeliveryService = new OrderDeliveryService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->orderDeliveryService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->orderDeliveryService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->orderDeliveryService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->orderDeliveryService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->orderDeliveryService->delete($id);
        return $this->success(null, '删除成功');
    }
    public function updateStatus(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->orderDeliveryService->update($id, $data);
        return $this->success(null, '更新成功');
    }
}
