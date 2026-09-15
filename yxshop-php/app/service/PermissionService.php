<?php

namespace app\service;

use app\repository\PermissionRepository;
use app\model\Permission;
use Exception;

/**
 * 权限服务类
 *
 * @property PermissionRepository $repository
 */
class PermissionService extends BaseService
{
    public function __construct(?PermissionRepository $repository = null)
    {
        parent::__construct($repository ?? new PermissionRepository());
    }

    public function getEnabled(int $appId = 0)
    {
        try {
            return $this->repository->getEnabled($appId);
        } catch (Exception $e) {
            $this->logError('获取已启用权限失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByType(int $permissionType, int $appId = 0)
    {
        try {
            return $this->repository->getByType($permissionType, $appId);
        } catch (Exception $e) {
            $this->logError('按类型获取权限失败', [
                'permission_type' => $permissionType,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByParent(int $parentId, int $appId = 0)
    {
        try {
            return $this->repository->getByParent($parentId, $appId);
        } catch (Exception $e) {
            $this->logError('按父级获取权限失败', [
                'parent_id' => $parentId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getRootPermissions(int $appId = 0)
    {
        try {
            return $this->repository->getRootPermissions($appId);
        } catch (Exception $e) {
            $this->logError('获取顶级权限失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getByMenu(int $menuId, int $appId = 0)
    {
        try {
            return $this->repository->getByMenu($menuId, $appId);
        } catch (Exception $e) {
            $this->logError('按菜单获取权限失败', [
                'menu_id' => $menuId,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPaginatedList(int $appId = 0, array $filters = [], int $pageSize = 20)
    {
        try {
            return $this->repository->getPaginatedList($appId, $filters, $pageSize);
        } catch (Exception $e) {
            $this->logError('获取权限分页列表失败', [
                'app_id' => $appId,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getPermissionTree(int $appId = 0): array
    {
        try {
            return $this->repository->getPermissionTree($appId);
        } catch (Exception $e) {
            $this->logError('获取权限树失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function enable(int $id)
    {
        try {
            $this->logInfo('启用权限开始', ['id' => $id]);
            $permission = $this->repository->findOrFail($id);
            $permission->status = Permission::STATUS_ENABLED;
            $permission->save();
            $this->logInfo('启用权限成功', ['id' => $id]);
            return $permission;
        } catch (Exception $e) {
            $this->logError('启用权限失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function disable(int $id)
    {
        try {
            $this->logInfo('禁用权限开始', ['id' => $id]);
            $permission = $this->repository->findOrFail($id);
            $permission->status = Permission::STATUS_DISABLED;
            $permission->save();
            $this->logInfo('禁用权限成功', ['id' => $id]);
            return $permission;
        } catch (Exception $e) {
            $this->logError('禁用权限失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function create(array $data)
    {
        try {
            $this->logInfo('创建权限开始', ['data' => $data]);
            $permission = $this->repository->create($data);
            $this->logInfo('创建权限成功', ['id' => $permission->id]);
            return $permission;
        } catch (Exception $e) {
            $this->logError('创建权限失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function update($id, array $data)
    {
        try {
            $this->logInfo('更新权限开始', ['id' => $id, 'data' => $data]);
            $permission = $this->repository->update($id, $data);
            $this->logInfo('更新权限成功', ['id' => $id]);
            return $permission;
        } catch (Exception $e) {
            $this->logError('更新权限失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
