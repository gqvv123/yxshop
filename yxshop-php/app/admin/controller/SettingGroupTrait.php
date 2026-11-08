<?php

namespace app\admin\controller;
use support\Request;

/**
 * SettingGroupTrait：从 SettingController.php 抽离的languages、languageStore、languageUpdate、languageDestroy、languageUpdateStatus、teamIndex、teamUpdateRole、teamInvite、teamUpdateStatus、teamDestroy等方法。
 *
 * 拆分目的：控制单文件体积、聚焦单一职责；方法实现保持原样，行为完全等价。
 *
 * @package app\admin\controller
 */
trait SettingGroupTrait
{
    public function languages(Request $request)
    {
        $languages = $this->languageService->getActive();
        return $this->success($languages);
    }

    public function languageStore(Request $request)
    {
        $data = $request->post();

        if (empty($data['name']) || empty($data['code'])) {
            return $this->error('语言名称和编码不能为空');
        }

        $result = $this->languageService->create($data);
        if (!$result) {
            return $this->error('添加失败');
        }
        return $this->success($result, '添加成功');
    }

    public function languageUpdate(Request $request, $id)
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->error('无效的语言ID');
        }

        $data = $request->post();
        $result = $this->languageService->update($id, $data);
        if (!$result) {
            return $this->error('更新失败');
        }
        return $this->success($result, '更新成功');
    }

    public function languageDestroy(Request $request, $id)
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->error('无效的语言ID');
        }

        $result = $this->languageService->delete($id);
        if (!$result) {
            return $this->error('删除失败');
        }
        return $this->success(null, '删除成功');
    }

    public function languageUpdateStatus(Request $request, $id)
    {
        if (empty($id) || !is_numeric($id)) {
            return $this->error('无效的语言ID');
        }

        $data = $request->post();
        if (!isset($data['status'])) {
            return $this->error('状态值不能为空');
        }

        $result = $this->languageService->update($id, ['status' => (int) $data['status']]);
        if (!$result) {
            return $this->error('操作失败');
        }
        return $this->success(null, '操作成功');
    }

    public function teamIndex(Request $request)
    {
        $appId = $this->getAppId($request);
        $list = $this->adminService->getTeamList($appId);
        return $this->success($list);
    }

    public function teamUpdateRole(Request $request, int $id)
    {
        $currentAdmin = $request->admin;
        if (empty($currentAdmin['is_super_admin'])) {
            return $this->error('无权操作', 403);
        }

        $roleId = $request->input('role_id');
        if (empty($roleId)) {
            return $this->error('角色不能为空');
        }

        try {
            $this->adminService->updateTeamRole($id, $roleId);
            return $this->success(null, '角色更新成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function teamInvite(Request $request)
    {
        $email = $request->input('email');
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->error('邮箱格式不正确');
        }

        $appId = $this->getAppId($request);
        $roleId = $request->input('role_id', 0);

        try {
            $admin = $this->adminService->inviteTeamMember($email, $appId, $roleId);
            return $this->success([
                'id' => $admin->id,
                'email' => $admin->email,
                'name' => $admin->username,
            ], '邀请成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage(), 409);
        }
    }

    public function teamUpdateStatus(Request $request, int $id)
    {
        $currentAdmin = $request->admin;
        if (empty($currentAdmin['is_super_admin'])) {
            return $this->error('无权操作', 403);
        }

        if ($currentAdmin['id'] == $id) {
            return $this->error('不能操作自己的状态', 422);
        }

        $status = $request->input('status', '');
        if (!in_array($status, ['active', 'disabled', 'pending'], true)) {
            return $this->error('状态值不合法', 422);
        }

        try {
            $admin = $this->adminService->find($id);
            if (!$admin) {
                return $this->error('用户不存在', 404);
            }
            if ($admin->is_super_admin) {
                return $this->error('不能操作超级管理员', 422);
            }
            $this->adminService->updateTeamStatus($id, $status);
            return $this->success(null, '状态更新成功');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }

    public function teamDestroy(Request $request, int $id)
    {
        $currentAdmin = $request->admin;
        if (empty($currentAdmin['is_super_admin'])) {
            return $this->error('无权操作', 403);
        }

        if ($currentAdmin['id'] == $id) {
            return $this->error('不能移除自己', 422);
        }

        try {
            $admin = $this->adminService->find($id);
            if (!$admin) {
                return $this->error('用户不存在', 404);
            }
            if ($admin->is_super_admin) {
                return $this->error('不能移除超级管理员', 422);
            }
            $this->adminService->deleteTeamMember($id);
            return $this->success(null, '成员已移除');
        } catch (\app\exception\BusinessException $e) {
            return $this->error($e->getMessage(), 404);
        }
    }
}
