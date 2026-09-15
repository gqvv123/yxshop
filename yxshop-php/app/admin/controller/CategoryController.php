<?php

namespace app\admin\controller;

use support\Request;
use app\service\CategoryService;
use app\validate\CategoryValidate;
use app\exception\ValidationException;

class CategoryController extends BaseController
{
    protected $categoryService;

    public function __construct()
    {
        parent::__construct();
        $this->categoryService = new CategoryService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $categories = $this->categoryService->getPaginatedList($appId, $pageSize);
        $topCategories = $this->categoryService->getTopCategories($appId);
        return $this->success([
            'data' => $categories['data'] ?? $categories,
            'total' => $categories['total'] ?? 0,
            'top_categories' => $topCategories,
        ]);
    }

    public function show(Request $request, $id)
    {
        $category = $this->categoryService->getCategoryById($id);
        if (!$category) {
            return $this->errorNotFound('分类不存在');
        }
        return $this->success($category);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);

        $validate = new CategoryValidate();
        $validate->failException(false);
        if (!$validate->scene('create')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $result = $this->categoryService->createCategory($data);
        return $this->success($result, '添加成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $data['id'] = $id;

        $validate = new CategoryValidate();
        $validate->failException(false);
        if (!$validate->scene('update')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $result = $this->categoryService->updateCategory($id, $data);
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $result = $this->categoryService->deleteCategory($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    /**
     * 更新分类状态（启用/禁用）
     */
    public function status(Request $request, $id)
    {
        $status = (int) $request->post('status');
        $result = $this->categoryService->updateCategoryStatus($id, $status);
        if (!$result) {
            return $this->error('更新状态失败');
        }
        return $this->success(null, $status === 1 ? '已启用' : '已禁用');
    }
}
