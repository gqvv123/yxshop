<?php

namespace app\admin\controller;

use support\Request;
use app\service\AuthService;
use app\service\RoleService;
use app\service\AdminService;
use app\service\AdminMenuService;

class AuthController extends BaseController
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
        $auths = $this->authService->getAuthTree($appId);
        return $this->success($auths);
    }

    public function role(Request $request)
    {
        $appId = $this->getAppId($request);
        $roles = $this->roleService->getRoleTree($appId);
        return $this->success($roles);
    }

    public function admin(Request $request)
    {
        $appId = $this->getAppId($request);
        $admins = $this->adminService->getAdminList($appId);
        $roles = $this->roleService->getRoleTree($appId);
        return $this->success(['admins' => $admins, 'roles' => $roles]);
    }

    public function storeAuth(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->authService->createAuth($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function updateAuth(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->authService->updateAuth($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function deleteAuth(Request $request, $id)
    {
        $result = $this->authService->deleteAuth($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function storeRole(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->roleService->createRole($data, $this->getAppId($request));
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function updateRole(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->roleService->updateRole($id, $data, $this->getAppId($request));
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function deleteRole(Request $request, $id)
    {
        $result = $this->roleService->deleteRole($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function storeAdmin(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->adminService->createAdmin($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function updateAdmin(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->adminService->updateAdmin($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function deleteAdmin(Request $request, $id)
    {
        $result = $this->adminService->deleteAdmin($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function menuList(Request $request)
    {
        $appId = $this->getAppId($request);
        $menus = $this->menuService->getMenuTree($appId);
        return $this->success($menus);
    }

    public function menuTree(Request $request)
    {
        $appId = $this->getAppId($request);
        $menus = $this->menuService->getMenuTree($appId);
        return $this->success($menus);
    }

    public function menuRead(Request $request, $id)
    {
        $menu = $this->menuService->getMenuById($id);
        if (!$menu) {
            return $this->error('菜单不存在');
        }
        return $this->success($menu);
    }

    public function menuStore(Request $request)
    {
        $data = $request->post();
        $data['app_id'] = $this->getAppId($request);
        $result = $this->menuService->createMenu($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function menuUpdate(Request $request, $id)
    {
        $data = $request->post();
        $result = $this->menuService->updateMenu($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function menuDestroy(Request $request, $id)
    {
        $result = $this->menuService->deleteMenu($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function menuSort(Request $request)
    {
        $data = $request->post();
        if (empty($data['items'])) {
            return $this->error('参数错误');
        }
        foreach ($data['items'] as $item) {
            if (isset($item['id']) && isset($item['sort'])) {
                $this->menuService->updateMenu($item['id'], ['sort' => $item['sort']]);
            }
        }
        return $this->success(null, '排序成功');
    }
}
