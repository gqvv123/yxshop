<?php

namespace app\service;

use app\repository\AdminMenuRepository;
use app\model\AdminMenu;
use app\exception\BusinessException;
use app\validate\MenuValidate;
use Exception;

/**
 * 菜单服务类
 *
 * @property AdminMenuRepository $repository
 */
class AdminMenuService extends BaseService
{
    public function __construct()
    {
        $this->repository = new AdminMenuRepository();
        parent::__construct($this->repository);
    }

    public function getMenuById($id)
    {
        return $this->repository->find($id);
    }

    /**
     * 获取菜单树
     */
    public function getMenuTree($appId = 0)
    {
        try {

            $tree = $this->repository->getMenuTree(0, $appId);

            return $tree;

        } catch (Exception $e) {
            $this->logError('获取菜单树失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取用户菜单
     */
    public function getUserMenus($userMenus, $appId = 0)
    {
        try {
            $this->logInfo('获取用户菜单开始', ['user_menus' => $userMenus, 'app_id' => $appId]);

            $menus = $this->repository->getUserMenus($userMenus, $appId);

            $this->logInfo('获取用户菜单成功', ['app_id' => $appId]);
            return $menus;

        } catch (Exception $e) {
            $this->logError('获取用户菜单失败', [
                'user_menus' => $userMenus,
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 创建菜单
     */
    public function createMenu(array $data)
    {
        try {
            $this->logInfo('创建菜单开始', ['data' => $data]);

            $this->validateWith(MenuValidate::class, 'create', $data);

            // 检查父菜单是否存在
            if (!empty($data['parent_id'])) {
                $parent = $this->repository->find($data['parent_id']);
                if (!$parent) {
                    throw new BusinessException('父菜单不存在');
                }
                $data['level'] = $parent->level + 1;
            } else {
                $data['level'] = 1;
            }

            $menu = $this->repository->create($data);

            $this->logInfo('创建菜单成功', ['menu_id' => $menu->id]);
            return $menu;

        } catch (Exception $e) {
            $this->logError('创建菜单失败', [
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 更新菜单
     */
    public function updateMenu($id, array $data)
    {
        try {
            $this->logInfo('更新菜单开始', ['id' => $id, 'data' => $data]);

            $this->validateWith(MenuValidate::class, 'update', $data);

            $menu = $this->repository->findOrFail($id);

            // 检查父菜单是否存在
            if (isset($data['parent_id']) && !empty($data['parent_id'])) {
                $parent = $this->repository->find($data['parent_id']);
                if (!$parent) {
                    throw new BusinessException('父菜单不存在');
                }
                $data['level'] = $parent->level + 1;
            }

            $menu = $this->repository->update($id, $data);

            $this->logInfo('更新菜单成功', ['menu_id' => $id]);
            return $menu;

        } catch (Exception $e) {
            $this->logError('更新菜单失败', [
                'id' => $id,
                'data' => $data,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 删除菜单
     */
    public function deleteMenu($id)
    {
        try {
            $this->logInfo('删除菜单开始', ['id' => $id]);

            $menu = $this->repository->findOrFail($id);

            // 检查是否有子菜单
            $children = $this->repository->countChildren($id);

            if ($children > 0) {
                throw new BusinessException('该菜单下有子菜单，无法删除');
            }

            $this->repository->delete($id);

            $this->logInfo('删除菜单成功', ['menu_id' => $id]);
            return true;

        } catch (Exception $e) {
            $this->logError('删除菜单失败', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * 获取菜单统计
     */
    public function getMenuStats($appId = 0)
    {
        try {
            return $this->repository->getMenuStats($appId);

        } catch (Exception $e) {
            $this->logError('获取菜单统计失败', [
                'app_id' => $appId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }
}
