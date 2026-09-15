<?php

namespace app\admin\controller;

use support\Request;
use app\service\AdminRoleService;

class RoleController extends BaseController
{
    protected $roleService;

    public function __construct()
    {
        parent::__construct();
        $this->roleService = new AdminRoleService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $keyword = $request->get('keyword', '');

        if ($keyword) {
            $roles = $this->roleService->searchRoles($keyword, $appId);
        } else {
            $roles = $this->roleService->getRoleList($appId);
        }

        return $this->success($roles);
    }

    public function show(Request $request, $id)
    {
        $role = $this->roleService->getRoleDetail($id);
        if (!$role) {
            return $this->error('角色不存在');
        }
        return $this->success($role);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);

        $result = $this->roleService->createRole($data);
        return $this->success($result, '创建成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->roleService->updateRole($id, $data);
        return $this->success(null, '更新成功');
    }

    public function destroy(Request $request, $id)
    {
        $this->roleService->deleteRole($id);
        return $this->success(null, '删除成功');
    }

    public function assignPermissions(Request $request, $id)
    {
        $authIds = $request->post('auth_ids', []);
        $this->roleService->assignPermissions($id, $authIds);
        return $this->success(null, '权限分配成功');
    }

    public function permissions(Request $request, $id)
    {
        $result = $this->roleService->getRolePermissions($id);
        return $this->success($result);
    }
}
