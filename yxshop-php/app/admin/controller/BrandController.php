<?php

namespace app\admin\controller;

use support\Request;
use app\service\BrandService;
use app\validate\BrandValidate;
use app\exception\ValidationException;

class BrandController extends BaseController
{
    protected $brandService;

    public function __construct()
    {
        parent::__construct();
        $this->brandService = new BrandService();
    }

    public function index(Request $request)
    {
        // 使用 $this->app_id（默认 0），而非 getAppId()（默认 10001）
        // 当 app_id 为 0 时不按 app_id 过滤，返回全部品牌
        $appId = (int) ($this->admin['app_id'] ?? 0);
        $pageSize = (int) $request->get('page_size', 20);
        $isHot = $request->get('is_hot');

        $brands = $this->brandService->getPaginatedList($appId, $pageSize, $isHot);
        return $this->paginate($brands);
    }

    public function show(Request $request, $id)
    {
        $brand = $this->brandService->findById($id);
        if (!$brand) {
            return $this->errorNotFound('品牌不存在');
        }
        return $this->success($brand);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);

        $validate = new BrandValidate();
        $validate->failException(false);
        if (!$validate->scene('create')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $result = $this->brandService->createBrand($data);
        return $this->success($result, '添加成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $data['id'] = $id;

        $validate = new BrandValidate();
        $validate->failException(false);
        if (!$validate->scene('update')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $result = $this->brandService->updateBrand($id, $data);
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $result = $this->brandService->deleteBrand($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status', 1);
        $result = $this->brandService->updateStatus($id, $status);
        if (!$result) {
            return $this->error('状态更新失败');
        }
        return $this->success(null, '状态更新成功');
    }

    public function batchStatus(Request $request)
    {
        $ids = $request->post('ids', []);
        $status = $request->post('status', 1);
        if (empty($ids)) {
            return $this->error('请选择要操作的数据');
        }
        $result = $this->brandService->batchUpdateStatus($ids, $status);
        if (!$result) {
            return $this->error('批量更新失败');
        }
        return $this->success(null, '批量更新成功');
    }

    public function batchDelete(Request $request)
    {
        $ids = $request->post('ids', []);
        if (empty($ids)) {
            return $this->error('请选择要删除的数据');
        }
        $result = $this->brandService->batchDelete($ids);
        if (!$result) {
            return $this->error('批量删除失败');
        }
        return $this->success(null, '批量删除成功');
    }
}
