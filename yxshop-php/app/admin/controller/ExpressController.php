<?php

namespace app\admin\controller;

use support\Request;
use app\service\ExpressService;
use app\validate\ExpressValidate;
use app\exception\ValidationException;

class ExpressController extends BaseController
{
    protected $expressService;

    public function __construct()
    {
        parent::__construct();
        $this->expressService = new ExpressService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $expresses = $this->expressService->getExpressList($appId);
        return $this->success($expresses);
    }

    public function show(Request $request, $id)
    {
        $express = $this->expressService->findOrFail($id);
        if (!$express) {
            return $this->errorNotFound('快递公司不存在');
        }
        return $this->success($express);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);

        $validate = new ExpressValidate();
        $validate->failException(false);
        if (!$validate->scene('create')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $result = $this->expressService->createExpress($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();

        $validate = new ExpressValidate();
        $validate->failException(false);
        if (!$validate->scene('update')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $result = $this->expressService->updateExpress($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $result = $this->expressService->deleteExpress($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = $request->post('status');
        $result = $this->expressService->updateExpress($id, ['status' => (int)$status]);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success(null, '更新成功');
    }
}
