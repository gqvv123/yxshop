<?php

namespace app\service;

use app\repository\AdminRepository;
use app\model\Admin;
use app\exception\BusinessException;
use app\validate\AdminValidate;
use Exception;

/**
 * 管理员服务类
 *
 * @property AdminRepository $repository
 */
class AdminService extends BaseService
{
    public function __construct()
    {
        $this->repository = new AdminRepository();
        parent::__construct($this->repository);
    }

    /**
     * 管理员登录
     */
    public function login($userName, $password, $appId = 0)
    {
        try {
            $this->logInfo('管理员登录开始', ['username' => $userName, 'app_id' => $appId]);

            $admin = $this->repository->findByUserName($userName);

            if (!$admin) {
                throw new BusinessException('用户名或密码错误');
            }

            if ($appId > 0 && $admin->app_id != $appId) {
                throw new BusinessException('无权限访问该应用');
            }

            if (!password_verify($password, $admin->password)) {
                throw new BusinessException('用户名或密码错误');
            }

            if ($admin->deleted_at) {
                throw new BusinessException('账户已被删除');
            }

            // 更新最后登录时间
            $admin->last_login_at = time();
            $admin->save();

            $this->logInfo('管理员登录成功', ['admin_id' => $admin->id]);
            return $admin;

        } catch (Exception $e) {
            $this->logError('管理员登录失败', [
                'username' => $userName,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建管理员
     */
    public function createAdmin(array $data)
    {
        try {
            $this->logInfo('创建管理员开始', ['data' => $data]);

            $this->validateWith(AdminValidate::class, 'create', $data);

            // 检查用户名是否重复
            $existing = $this->repository->findByUserName($data['username']);
            if ($existing) {
                throw new BusinessException('用户名已存在');
            }

            // 加密密码
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);

            $admin = $this->repository->create($data);

            $this->logInfo('创建管理员成功', ['admin_id' => $admin->id]);
            return $admin;

        } catch (Exception $e) {
            $this->logError('创建管理员失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新管理员
     */
    public function updateAdmin($id, array $data)
    {
        try {
            $this->logInfo('更新管理员开始', ['id' => $id, 'data' => $data]);

            $this->validateWith(AdminValidate::class, 'update', $data);

            $admin = $this->repository->findOrFail($id);

            // 检查用户名是否重复
            if (isset($data['username'])) {
                $existing = $this->repository->findByUserName($data['username']);
                if ($existing && $existing->id != $id) {
                    throw new BusinessException('用户名已存在');
                }
            }

            // 如果更新密码，需要加密
            if (isset($data['password'])) {
                $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            }

            $admin = $this->repository->update($id, $data);

            $this->logInfo('更新管理员成功', ['admin_id' => $id]);
            return $admin;

        } catch (Exception $e) {
            $this->logError('更新管理员失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除管理员
     */
    public function deleteAdmin($id)
    {
        try {
            $this->logInfo('删除管理员开始', ['id' => $id]);

            $admin = $this->repository->findOrFail($id);

            // 不能删除超级管理员（字段对齐 Admin 表实际字段 is_super_admin）
            if (!empty($admin->is_super_admin)) {
                throw new BusinessException('不能删除超级管理员');
            }

            $this->repository->delete($id);

            $this->logInfo('删除管理员成功', ['admin_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除管理员失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取管理员列表
     */
    public function getAdminList($appId = 0, $roleId = null)
    {
        try {
            return $this->repository->getAdmins($appId, $roleId);

        } catch (Exception $e) {
            $this->logError('获取管理员列表失败', [
                'app_id' => $appId,
                'role_id' => $roleId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取超级管理员
     */
    public function getSuperAdmins($appId = 0)
    {
        try {
            return $this->repository->getSuperAdmins($appId);

        } catch (Exception $e) {
            $this->logError('获取超级管理员失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 搜索管理员
     */
    public function searchAdmins($keyword, $appId = 0)
    {
        try {
            return $this->repository->searchAdmins($keyword, $appId);

        } catch (Exception $e) {
            $this->logError('搜索管理员失败', [
                'keyword' => $keyword,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取管理员统计
     */
    public function getAdminStats($appId = 0)
    {
        try {
            return $this->repository->getAdminStats($appId);

        } catch (Exception $e) {
            $this->logError('获取管理员统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    public function getAdminMenus($admin): array
    {
        $menuRepo = new \app\repository\AdminMenuRepository();

        if (!empty($admin->is_super_admin)) {
            $menuIds = [];
        } else {
            $roleRepo = new \app\repository\RoleRepository();
            $menuIds = $roleRepo->getAuthIds($admin->role_id ?? 0);
        }

        return $menuRepo->getAdminMenuTree($menuIds, !empty($admin->is_super_admin));
    }

    /**
     * 获取团队列表
     */
    public function getTeamList(int $appId)
    {
        $admins = $this->repository->getAdmins($appId);
        return $admins->map(function ($admin) {
            return [
                'id' => $admin->id,
                'name' => $admin->username,
                'email' => $admin->email ?? '',
                'role' => $admin->role ? $admin->role->name : '',
                'role_id' => $admin->role_id,
                'status' => $admin->is_super_admin ? 'active' : ($admin->status ?? 'active'),
                'last_active' => $admin->last_login_at ? date('Y-m-d H:i:s', (int) $admin->last_login_at) : null,
                'is_super_admin' => $admin->is_super_admin,
            ];
        });
    }

    /**
     * 更新团队成员角色
     */
    public function updateTeamRole(int $id, $roleId)
    {
        $admin = $this->repository->find($id);
        if (!$admin) {
            throw new BusinessException('用户不存在');
        }
        $admin->role_id = $roleId;
        $admin->save();
        return $admin;
    }

    /**
     * 邀请团队成员
     */
    public function inviteTeamMember(string $email, int $appId, $roleId = 0)
    {
        $exists = $this->repository->exists(['email' => $email, 'app_id' => $appId]);
        if ($exists) {
            throw new BusinessException('该邮箱已存在');
        }

        $randomPassword = bin2hex(random_bytes(8));
        $admin = new Admin();
        $admin->email = $email;
        $admin->username = explode('@', $email)[0];
        $admin->password = password_hash($randomPassword, PASSWORD_DEFAULT);
        $admin->app_id = $appId;
        $admin->status = 'pending';
        $admin->is_super_admin = 0;
        $admin->role_id = $roleId;
        $admin->save();

        return $admin;
    }

    /**
     * 更新团队成员状态
     */
    public function updateTeamStatus(int $id, string $status)
    {
        $admin = $this->repository->find($id);
        if (!$admin) {
            throw new BusinessException('用户不存在');
        }
        $admin->status = $status;
        $admin->save();
        return $admin;
    }

    /**
     * 删除团队成员
     */
    public function deleteTeamMember(int $id)
    {
        return $this->repository->delete($id);
    }

    /**
     * 更新个人资料
     */
    public function updateProfile(int $adminId, array $data)
    {
        $admin = $this->repository->find($adminId);
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $allowed = ['nickname', 'phone', 'email', 'avatar', 'backend_setting'];
        $updateData = array_intersect_key($data, array_flip($allowed));

        if (empty($updateData)) {
            throw new BusinessException('没有可更新的字段');
        }

        foreach ($updateData as $key => $value) {
            $admin->$key = $value;
        }
        $admin->save();
        return $admin;
    }

    /**
     * 修改密码
     */
    public function changePassword(int $adminId, string $oldPassword, string $newPassword)
    {
        $admin = $this->repository->find($adminId);
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        if (!$admin->checkPassword($oldPassword)) {
            throw new BusinessException('原密码不正确');
        }

        $oldHash = $admin->password;
        $admin->setPassword($newPassword);
        $admin->password_changed_at = time();
        $admin->force_password_change = 0;
        $admin->save();

        return $admin;
    }

    /**
     * 强制修改密码
     */
    public function forceChangePassword(int $adminId, string $newPassword)
    {
        $admin = $this->repository->find($adminId);
        if (!$admin) {
            throw new BusinessException('未登录');
        }

        $oldHash = $admin->password;
        $admin->setPassword($newPassword);
        $admin->password_changed_at = time();
        $admin->force_password_change = 0;
        $admin->save();

        return $admin;
    }

    /**
     * 设置2FA密钥
     */
    public function setTwoFactorSecret(int $adminId, string $secret)
    {
        $admin = $this->repository->find($adminId);
        if (!$admin) {
            throw new BusinessException('未登录');
        }
        $admin->two_factor_secret = $secret;
        $admin->save();
        return $admin;
    }

    /**
     * 启用2FA
     */
    public function enableTwoFactor(int $adminId)
    {
        $admin = $this->repository->find($adminId);
        if (!$admin) {
            throw new BusinessException('未登录');
        }
        $admin->two_factor_enabled = 1;
        $admin->save();
        return $admin;
    }

    /**
     * 禁用2FA
     */
    public function disableTwoFactor(int $adminId)
    {
        $admin = $this->repository->find($adminId);
        if (!$admin) {
            throw new BusinessException('未登录');
        }
        $admin->two_factor_enabled = 0;
        $admin->two_factor_secret = null;
        $admin->save();
        return $admin;
    }

    /**
     * 获取2FA状态
     */
    public function getTwoFactorStatus(int $adminId): array
    {
        $admin = $this->repository->find($adminId);
        if (!$admin) {
            throw new BusinessException('未登录');
        }
        return ['enabled' => (bool) $admin->two_factor_enabled];
    }

    /**
     * 根据用户名查找管理员
     */
    public function findByUsername(string $username)
    {
        return $this->repository->findByUserName($username);
    }
}
