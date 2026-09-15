<?php

namespace app\admin\controller;

use support\Request;
use app\service\SpecService;

class SpecController extends BaseController
{
    protected $specService;

    public function __construct()
    {
        parent::__construct();
        $this->specService = new SpecService();
    }

    /**
     * 规格分页列表 / 启用规格列表（?enabled=1）
     */
    public function index(Request $request)
    {
        $appId = $this->getAppId($request);

        // 供商品编辑选择：GET /specs?enabled=1
        if ($request->get('enabled')) {
            $result = $this->specService->getEnabledList($appId);
            return $this->success($result);
        }

        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->specService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    /**
     * 规格详情
     */
    public function show(Request $request, $id)
    {
        $result = $this->specService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    /**
     * 创建规格（可附带规格值）
     */
    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->specService->create($data);
        return $this->success($result, '创建成功');
    }

    /**
     * 更新规格（可附带规格值全量覆盖）
     */
    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->specService->update($id, $data);
        return $this->success($result, '更新成功');
    }

    /**
     * 删除规格（连同规格值）
     */
    public function destroy(Request $request, $id)
    {
        $this->specService->delete($id);
        return $this->success(null, '删除成功');
    }

    /**
     * 更新规格状态
     */
    public function status(Request $request, $id)
    {
        $status = (int) $request->post('status');
        $result = $this->specService->updateStatus($id, $status);
        if (!$result) {
            return $this->error('更新状态失败');
        }
        return $this->success(null, $status === 1 ? '已启用' : '已禁用');
    }
}
