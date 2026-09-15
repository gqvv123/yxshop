<?php

namespace app\service;

use app\repository\AdminAuthRepository;
use app\model\AdminMenu;
use app\exception\BusinessException;
use app\validate\AdminAuthValidate;
use Exception;

/**
 * 权限服务
 *
 * @property AdminAuthRepository $repository
 */
class AdminAuthService extends BaseService
{
    public function __construct()
    {
        $this->repository = new AdminAuthRepository();
        parent::__construct($this->repository);
    }

    /**
     * 权限校验（简化实现：如有需要可在此接入角色/权限表）
     */
    public function check(string $url, ?array $admin, $appId = 0): bool
    {
        if (empty($admin)) {
            return false;
        }
        // 超管直接放行
        if (!empty($admin['is_super_admin'])) {
            return true;
        }
        // @roadmap 细粒度权限校验属于企业版功能（AdminPermissionMiddleware 已实现基础 RBAC）
        return true;
    }

    /**
     * 获取权限树
     */
    public function getAuthTree($appId = 0)
    {
        try {
            $this->logInfo('获取权限树开始', ['app_id' => $appId]);

            $tree = $this->repository->getAuthTree(0, $appId);

            $this->logInfo('获取权限树成功', ['app_id' => $appId]);
            return $tree;

        } catch (Exception $e) {
            $this->logError('获取权限树失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取角色权限
     */
    public function getRoleAuths($roleId, $appId = 0)
    {
        try {
            $this->logInfo('获取角色权限开始', ['role_id' => $roleId, 'app_id' => $appId]);

            $auths = $this->repository->getRoleAuths($roleId, $appId);

            $this->logInfo('获取角色权限成功', ['role_id' => $roleId]);
            return $auths;

        } catch (Exception $e) {
            $this->logError('获取角色权限失败', [
                'role_id' => $roleId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建权限
     */
    public function createAuth(array $data)
    {
        try {
            $this->logInfo('创建权限开始', ['data' => $data]);

            $this->validateWith(AdminAuthValidate::class, 'create', $data);

            // 检查父权限是否存在
            if (!empty($data['parent_id'])) {
                $parent = $this->repository->find($data['parent_id']);
                if (!$parent) {
                    throw new BusinessException('父权限不存在');
                }
                $data['level'] = $parent->level + 1;
            } else {
                $data['level'] = 1;
            }

            $auth = $this->repository->create($data);

            $this->logInfo('创建权限成功', ['auth_id' => $auth->id]);
            return $auth;

        } catch (Exception $e) {
            $this->logError('创建权限失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新权限
     */
    public function updateAuth($id, array $data)
    {
        try {
            $this->logInfo('更新权限开始', ['id' => $id, 'data' => $data]);

            $this->validateWith(AdminAuthValidate::class, 'update', $data);

            $auth = $this->repository->findOrFail($id);

            // 检查父权限是否存在
            if (isset($data['parent_id']) && !empty($data['parent_id'])) {
                $parent = $this->repository->find($data['parent_id']);
                if (!$parent) {
                    throw new BusinessException('父权限不存在');
                }
                $data['level'] = $parent->level + 1;
            }

            $auth = $this->repository->update($id, $data);

            $this->logInfo('更新权限成功', ['auth_id' => $id]);
            return $auth;

        } catch (Exception $e) {
            $this->logError('更新权限失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除权限
     */
    public function deleteAuth($id)
    {
        try {
            $this->logInfo('删除权限开始', ['id' => $id]);

            $auth = $this->repository->findOrFail($id);

            // 检查是否有子权限
            $children = $this->repository->countChildren($id);

            if ($children > 0) {
                throw new BusinessException('该权限下有子权限，无法删除');
            }

            // 检查是否有角色使用
            if ($auth->roles()->count() > 0) {
                throw new BusinessException('该权限有角色使用，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除权限成功', ['auth_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除权限失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取权限统计
     */
    public function getAuthStats($appId = 0)
    {
        try {
            return $this->repository->getAuthStats($appId);

        } catch (Exception $e) {
            $this->logError('获取权限统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 根据角色获取权限码列表
     */
    public function getPermissionsByRole(int $roleId): array
    {
        try {
            $roleRepo = new \app\repository\RoleRepository();
            $authIds = $roleRepo->getAuthIds($roleId);
            if (empty($authIds)) {
                return [];
            }
            $menus = $roleRepo->getAllMenus()->whereIn('id', $authIds);
            return $menus->map(fn($m) => $m->permission ?? $m->path ?? $m->name)
                ->filter()->unique()->values()->toArray();
        } catch (Exception $e) {
            $this->logError('根据角色获取权限码失败', [
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}