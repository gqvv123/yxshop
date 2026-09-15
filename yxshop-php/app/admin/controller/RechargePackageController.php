<?php

namespace app\admin\controller;

use support\Request;
use app\service\RechargePackageService;

class RechargePackageController extends BaseController
{
    protected $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new RechargePackageService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $filters = [];
        if ($request->get('status') !== null) {
            $filters['status'] = $request->get('status');
        }
        $pageSize = (int) $request->get('page_size', 20);
        $result = $this->service->getPaginatedList($appId, $filters, $pageSize);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->service->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->service->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->service->update($id, $data);
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->service->delete($id);
        return $this->success(null, '删除成功');
    }

    public function enable(Request $request, $id)
    {
        $result = $this->service->enable($id);
        return $this->success($result, '启用成功');
    }

    public function disable(Request $request, $id)
    {
        $result = $this->service->disable($id);
        return $this->success($result, '禁用成功');
    }
}
