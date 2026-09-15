<?php

namespace app\admin\controller;

use support\Request;
use app\service\PromItemService;

class PromItemController extends BaseController
{
    protected $promItemService;

    public function __construct()
    {
        parent::__construct();
        $this->promItemService = new PromItemService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->promItemService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->promItemService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->promItemService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->promItemService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->promItemService->delete($id);
        return $this->success(null, '删除成功');
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要删除的记录');
        }
        $this->promItemService->batchDelete($ids);
        return $this->success(null, '批量删除成功');
    }
}