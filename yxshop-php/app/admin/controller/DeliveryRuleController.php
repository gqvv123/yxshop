<?php

namespace app\admin\controller;

use support\Request;
use app\service\DeliveryRuleService;
use app\service\RegionService;

class DeliveryRuleController extends BaseController
{
    protected $deliveryRuleService;
    protected $regionService;

    public function __construct()
    {
        parent::__construct();
        $this->deliveryRuleService = new DeliveryRuleService();
        $this->regionService = new RegionService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $page = (int) $request->get('page', 1);
        $limit = (int) $request->get('page_size', 20);
        $ruleName = $request->get('rule_name', '');
        $status = $request->get('status', '');

        $rules = $this->deliveryRuleService->getRuleList($page, $limit, [
            'rule_name' => $ruleName,
            'status' => $status,
            'app_id' => $appId
        ]);
        return $this->success($rules);
    }

    public function show(Request $request, $id)
    {
        $rule = $this->deliveryRuleService->getRuleById($id);
        if (!$rule) {
            return $this->errorNotFound('配送规则不存在');
        }
        return $this->success($rule);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->deliveryRuleService->createRule($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->deliveryRuleService->updateRule($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $result = $this->deliveryRuleService->deleteRule($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status');
        $result = $this->deliveryRuleService->updateRuleStatus($id, $status);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success(null, '更新成功');
    }

    public function stats(Request $request)
    {
        $appId = $this->getAppId($request);
        $stats = $this->deliveryRuleService->getRuleStats($appId);
        return $this->success($stats);
    }
}
