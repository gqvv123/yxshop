<?php

namespace app\service;

use app\repository\AdminRoleRepository;
use app\repository\RoleRepository;
use app\model\AdminRole;
use app\model\AdminMenu;
use app\exception\BusinessException;
use app\validate\AdminRoleValidate;
use Exception;

class AdminRoleService extends BaseService
{
    protected $roleRepository;

    public function __construct(?AdminRoleRepository $repository = null)
    {
        parent::__construct($repository ?? new AdminRoleRepository());
        $this->roleRepository = new RoleRepository();
    }

    public function getRoleList($appId = 0)
    {
        try {
            return $this->roleRepository->getRoles($appId);
        } catch (Exception $e) {
            $this->logError('获取角色列表失败', ['app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getRoleTree($appId = 0)
    {
        return $this->getRoleList($appId);
    }

    public function getRoleDetail($id)
    {
        try {
            $role = $this->roleRepository->findWithAuths($id);
            if (!$role) {
                return null;
            }

            $authIds = $role->getAuthIds();

            return [
                'id' => $role->id,
                'role_name' => $role->role_name,
                'role_desc' => $role->role_desc,
                'app_id' => $role->app_id,
                'created_at' => $role->created_at,
                'auth_ids' => $authIds,
                'admin_count' => $role->admins()->count(),
            ];
        } catch (Exception $e) {
            $this->logError('获取角色详情失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function createRole(array $data)
    {
        try {
            $this->validateWith(AdminRoleValidate::class, 'create', $data);

            $roleName = trim($data['role_name'] ?? '');
            $appId = $data['app_id'] ?? 0;

            if ($roleName === '') {
                throw new BusinessException('角色名称不能为空');
            }

            $existing = $this->roleRepository->findByRoleName($roleName, $appId);
            if ($existing) {
                throw new BusinessException('角色名称已存在');
            }

            $role = $this->roleRepository->create([
                'role_name' => $roleName,
                'role_desc' => trim($data['role_desc'] ?? ''),
                'deleted_at' => 0,
                'app_id' => $appId,
            ]);

            $authIds = $data['auth_ids'] ?? [];
            if (!empty($authIds)) {
                $this->roleRepository->syncAuths($role->id, $authIds);
            }

            $this->logInfo('创建角色成功', ['role_id' => $role->id]);
            return $role;
        } catch (Exception $e) {
            $this->logError('创建角色失败', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function updateRole($id, array $data)
    {
        try {
            $this->validateWith(AdminRoleValidate::class, 'update', $data);

            $role = $this->roleRepository->findOrFail($id);
            if (isset($data['role_name'])) {
                $roleName = trim($data['role_name']);
                $existing = $this->roleRepository->findByRoleNameExclude($roleName, $id, $role->app_id);
                if ($existing) {
                    throw new BusinessException('角色名称已存在');
                }
            }

            $updateData = [];
            if (isset($data['role_name'])) {
                $updateData['role_name'] = trim($data['role_name']);
            }
            if (isset($data['role_desc'])) {
                $updateData['role_desc'] = trim($data['role_desc']);
            }

            if (!empty($updateData)) {
                $this->roleRepository->update($id, $updateData);
            }

            if (isset($data['auth_ids'])) {
                $this->roleRepository->syncAuths($id, $data['auth_ids']);
            }

            $this->logInfo('更新角色成功', ['role_id' => $id]);
            return true;
        } catch (Exception $e) {
            $this->logError('更新角色失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function deleteRole($id)
    {
        try {
            $role = $this->roleRepository->findOrFail($id);

            $adminCount = $this->roleRepository->getAdminCount($id);
            if ($adminCount > 0) {
                throw new BusinessException("该角色下有 {$adminCount} 个管理员，无法删除");
            }

            $this->roleRepository->softDelete($id);
            $this->roleRepository->detachAuths($id);

            $this->logInfo('删除角色成功', ['role_id' => $id]);
            return true;
        } catch (Exception $e) {
            $this->logError('删除角色失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function assignPermissions($id, array $authIds)
    {
        try {
            $role = $this->roleRepository->findOrFail($id);

            $this->roleRepository->syncAuths($id, $authIds);

            $this->logInfo('权限分配成功', ['role_id' => $id, 'auth_count' => count($authIds)]);
            return true;
        } catch (Exception $e) {
            $this->logError('权限分配失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getRolePermissions($id)
    {
        try {
            $role = $this->roleRepository->findWithAuths($id);
            if (!$role) {
                throw new BusinessException('角色不存在');
            }

            $authIds = $role->getAuthIds();
            $menus = $this->roleRepository->getAllMenus();

            return [
                'role' => [
                    'id' => $role->id,
                    'role_name' => $role->role_name,
                    'role_desc' => $role->role_desc,
                ],
                'auth_ids' => $authIds,
                'menus' => $menus,
            ];
        } catch (Exception $e) {
            $this->logError('获取角色权限失败', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function searchRoles($keyword, $appId = 0)
    {
        try {
            return $this->roleRepository->searchRoles($keyword, $appId);
        } catch (Exception $e) {
            $this->logError('搜索角色失败', ['keyword' => $keyword, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function getRoleStats($appId = 0)
    {
        try {
            return $this->roleRepository->getRoleStats($appId);
        } catch (Exception $e) {
            $this->logError('获取角色统计失败', ['app_id' => $appId, 'error' => $e->getMessage()]);
            throw $e;
        }
    }
}
