<?php

namespace app\service;

use app\repository\AuthRepository;
use app\model\AdminMenu;
use app\validate\AuthValidate;

class AuthService extends BaseService
{
    public function __construct()
    {
        parent::__construct(new AuthRepository());
    }

    public function getAuthTree(int $appId = 0)
    {
        $auths = $this->repository->getTreeByAppId($appId);
        return build_menu_tree($auths->toArray());
    }

    public function createAuth(array $data)
    {
        $this->validateWith(AuthValidate::class, 'create', $data);
        $this->logInfo('创建权限', ['data' => $data]);
        return $this->repository->create($data);
    }

    public function updateAuth(int $id, array $data)
    {
        $this->validateWith(AuthValidate::class, 'update', $data);
        $this->logInfo('更新权限', ['id' => $id]);
        return $this->repository->update($id, $data);
    }

    public function deleteAuth(int $id)
    {
        $this->logInfo('删除权限', ['id' => $id]);
        return $this->repository->softDelete($id);
    }
}
