<?php

namespace app\admin\controller;

use support\Request;
use app\service\AppPageService;

class AppPageController extends BaseController
{
    protected $appPageService;

    public function __construct()
    {
        parent::__construct();
        $this->appPageService = new AppPageService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pages = $this->appPageService->getPageList($appId);
        return $this->success($pages);
    }

    public function show(Request $request, $id)
    {
        $page = $this->appPageService->getPageById($id);
        if (!$page) {
            return $this->errorNotFound('页面不存在');
        }
        return $this->success($page);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->appPageService->createPage($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->appPageService->updatePage($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $result = $this->appPageService->deletePage($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status');
        $result = $this->appPageService->updatePageStatus($id, $status);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success(null, '更新成功');
    }

    public function preview(Request $request, $id)
    {
        $page = $this->appPageService->getPageById($id);
        if (!$page) {
            return $this->errorNotFound('页面不存在');
        }
        return $this->success($page);
    }
}
