<?php

namespace app\admin\controller;

use support\Request;
use app\service\DeliveryService;

class DeliveryController extends BaseController
{
    protected $deliveryService;

    public function __construct()
    {
        parent::__construct();
        $this->deliveryService = new DeliveryService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->deliveryService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->deliveryService->create($data);
        return $this->success($result);
    }

    public function show(Request $request, $id)
    {
        $result = $this->deliveryService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->deliveryService->update($id, $data);
        return $this->success($result);
    }

    public function destroy(Request $request, $id)
    {
        $this->deliveryService->delete($id);
        return $this->success(null, '删除成功');
    }
}
