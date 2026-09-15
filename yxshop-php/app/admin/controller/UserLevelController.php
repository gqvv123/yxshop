<?php

namespace app\admin\controller;

use support\Request;
use app\service\UserLevelService;

class UserLevelController extends BaseController
{
    protected $userLevelService;

    public function __construct()
    {
        parent::__construct();
        $this->userLevelService = new UserLevelService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->userLevelService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->userLevelService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->userLevelService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->userLevelService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->userLevelService->delete($id);
        return $this->success(null, '删除成功');
    }

    public function status(Request $request, $id)
    {
        $status = (int) $request->post('status');
        $this->userLevelService->updateStatus($id, $status);
        return $this->success(null, $status === 1 ? '已启用' : '已禁用');
    }
}
