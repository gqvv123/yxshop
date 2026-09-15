<?php

namespace app\admin\controller;

use support\Request;
use app\service\ApplyService;
use app\service\UserService;

class ApplyController extends BaseController
{
    protected $applyService;
    protected $userService;

    public function __construct()
    {
        parent::__construct();
        $this->applyService = new ApplyService();
        $this->userService = new UserService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $limit = (int) $request->get('page_size', 20);
        $applyType = $request->get('apply_type', '');
        $applyStatus = $request->get('apply_status', '');
        $applies = $this->applyService->getApplyList(null, $applyType, $applyStatus, $appId, $limit);
        return $this->success($applies);
    }

    public function show(Request $request, $id)
    {
        $apply = $this->applyService->findOrFail($id);
        if (!$apply) {
            return $this->errorNotFound('申请记录不存在');
        }
        $user = $this->userService->findOrFail($apply->user_id);
        return $this->success(['apply' => $apply, 'user' => $user]);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->applyService->updateApply($id, $data);
        if (!$result) {
            return $this->error('处理失败');
        }
        return $this->success($result, '处理成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status');
        $remark = $request->post('remark', '');
        $result = $this->applyService->updateApply($id, ['apply_status' => $status, 'audit_remark' => $remark]);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success(null, '更新成功');
    }

    public function stats(Request $request)
    {
        $appId = $this->getAppId($request);
        $stats = $this->applyService->getApplyStats($appId);
        return $this->success($stats);
    }

    public function batchProcess(Request $request)
    {
        $ids = (array)$request->post('ids', []);
        if (empty($ids)) {
            return $this->error('缺少ids');
        }
        $updated = $this->applyService->batchProcess($ids);
        return $updated ? $this->success(null, '批量处理成功') : $this->error('批量处理失败');
    }

    public function export(Request $request)
    {
        $data = $this->applyService->getAll();
        return $this->success($data);
    }
}
