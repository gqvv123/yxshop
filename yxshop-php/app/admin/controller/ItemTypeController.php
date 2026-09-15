<?php

namespace app\admin\controller;

use support\Request;
use app\service\ItemTypeService;

class ItemTypeController extends BaseController
{
    protected $itemTypeService;

    public function __construct()
    {
        parent::__construct();
        $this->itemTypeService = new ItemTypeService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->itemTypeService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->itemTypeService->createType($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->itemTypeService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->itemTypeService->updateType($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->itemTypeService->deleteType($id);
        return $this->success(null, '删除成功');
    }

    public function status(Request $request, $id)
    {
        $status = (int) $request->post('status');
        $result = $this->itemTypeService->updateStatus($id, $status);
        if (!$result) {
            return $this->error('更新状态失败');
        }
        return $this->success(null, $status === 1 ? '已启用' : '已禁用');
    }
}
