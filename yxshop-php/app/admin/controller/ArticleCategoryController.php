<?php

namespace app\admin\controller;

use support\Request;
use app\service\ArticleCategoryService;

class ArticleCategoryController extends BaseController
{
    protected $categoryService;

    public function __construct()
    {
        parent::__construct();
        $this->categoryService = new ArticleCategoryService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $categories = $this->categoryService->getCategoryTree($appId);
        return $this->success($categories);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->categoryService->create($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->categoryService->update($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $result = $this->categoryService->delete($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }
}