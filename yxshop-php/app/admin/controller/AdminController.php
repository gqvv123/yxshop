<?php

namespace app\admin\controller;

use support\Request;
use app\service\AdminService;
use app\service\RoleService;
use app\validate\AdminValidate;
use app\exception\ValidationException;

class AdminController extends BaseController
{
    protected $roleService;

    public function __construct()
    {
        parent::__construct();
        $this->roleService = new RoleService();
    }

    public function index(Request $request)
    {
        $appId = $this->getAppId($request);
        $keyword = $request->get('keyword', '');
        $admins = $this->adminService->searchAdmins($keyword, $appId);

        $result = [];
        foreach ($admins as $admin) {
            $item = is_array($admin) ? $admin : $admin->toArray();
            $item['is_locked'] = ($admin->locked_until ?? 0) > time();
            $item['login_fail_count'] = $admin->login_fail_count ?? 0;
            $item['locked_until'] = $admin->locked_until ?? null;
            $item['role_name'] = $admin->role ? $admin->role->role_name : '';
            $result[] = $item;
        }

        return $this->success($result);
    }

    public function show(Request $request, $id)
    {
        $admin = $this->adminService->findOrFail($id);
        return $this->success($admin);
    }

    public function store(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $validate = new AdminValidate();
        $validate->failException(false);
        if (!$validate->scene('create')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $username = trim($request->post('username', ''));
        $password = trim($request->post('password', ''));
        $roleId = (int)$request->post('role_id', 0);
        if ($username === '' || $password === '') {
            return $this->error('用户名与密码不能为空');
        }

        $result = $this->adminService->createAdmin([
            'username' => $username,
            'password' => $password,
            'role_id' => $roleId,
            'app_id' => $this->getAppId($request),
            'status' => 1,
        ]);
        return $this->success($result, '创建成功');
    }

    public function update(Request $request, $id)
    {
        $data = $request->post();
        $validate = new AdminValidate();
        $validate->failException(false);
        if (!$validate->scene('update')->check($data)) {
            throw new ValidationException($validate->getError());
        }

        $nickname = trim($request->post('nickname', ''));
        $roleId = (int)$request->post('role_id', 0);
        $status = (int)$request->post('status', 1);
        $this->adminService->updateAdmin($id, [
            'nickname' => $nickname,
            'role_id' => $roleId,
            'status' => $status,
        ]);
        return $this->success(null, '更新成功');
    }

    public function updateStatus(Request $request, $id)
    {
        $status = (int)$request->post('status', 1);
        $this->adminService->updateAdmin($id, ['status' => $status]);
        return $this->success(null, '状态更新成功');
    }

    public function resetPassword(Request $request, $id)
    {
        $password = trim($request->post('password', ''));
        if ($password === '') {
            return $this->error('新密码不能为空');
        }
        $admin = $this->adminService->findOrFail($id);
        $admin->setPassword($password);
        $admin->force_password_change = 1;
        $admin->password_changed_at = null;
        $admin->locked_until = null;
        $admin->login_fail_count = 0;
        $admin->save();
        return $this->success(null, '密码已重置，该管理员下次登录需修改密码');
    }

    public function destroy(Request $request, $id)
    {
        $this->adminService->deleteAdmin($id);
        return $this->success(null, '删除成功');
    }
}
