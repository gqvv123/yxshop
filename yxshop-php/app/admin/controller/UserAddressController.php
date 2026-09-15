<?php

namespace app\admin\controller;

use support\Request;
use app\service\UserAddressService;

class UserAddressController extends BaseController
{
    protected $userAddressService;

    public function __construct()
    {
        parent::__construct();
        $this->userAddressService = new UserAddressService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $pageSize = (int) $request->get('page_size', 20);
        $keyword = $request->get('keyword', '');
        $result = $this->userAddressService->getList($appId, $pageSize, $keyword);
        return $this->success($result);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->userAddressService->create($data);
        return $this->success($result, '创建成功');
    }

    public function show(Request $request, $id)
    {
        $result = $this->userAddressService->getDetail($id);
        if (!$result) {
            return $this->error('记录不存在');
        }
        return $this->success($result);
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->userAddressService->update($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->userAddressService->delete($id);
        return $this->success(null, '删除成功');
    }
    public function setDefault(Request $request, $id)
    {
        $this->userAddressService->setDefault($id);
        return $this->success(null, '设置成功');
    }
}
