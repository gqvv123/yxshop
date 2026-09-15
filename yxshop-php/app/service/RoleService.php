<?php

namespace app\service;

use app\repository\RoleRepository;
use app\model\AdminRole;
use app\validate\AdminRoleValidate;

class RoleService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new RoleRepository());
    }

    public function getRoleTree(int $appId = 0)
    {
        return $this->repository->getRoleTree($appId);
    }

    public function createRole(array $data, int $appId = 0)
    {
        $this->logInfo('创建角色', ['data' => $data]);

        unset($data['auth_ids']);

        $role = $this->repository->create($data);

        return $role;
    }

    public function updateRole(int $id, array $data, int $appId = 0)
    {
        $this->logInfo('更新角色', ['id' => $id]);

        unset($data['auth_ids']);

        $role = $this->repository->update($id, $data);

        return $role;
    }

    public function deleteRole(int $id)
    {
        $this->logInfo('删除角色', ['id' => $id]);
        return $this->repository->softDelete($id);
    }
}
