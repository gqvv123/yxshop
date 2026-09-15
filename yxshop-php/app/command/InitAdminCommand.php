<?php

namespace app\command;

use support\Command;
use app\model\Admin;
use app\model\Auth;
use app\model\Menu;

/**
 * 初始化管理员命令
 */
class InitAdminCommand extends Command
{
    protected static $defaultName = 'init:admin';
    protected static $defaultDescription = '初始化默认管理员账户';

    public function handle()
    {
        $this->info('开始初始化默认管理员账户...');

        try {
            // 检查是否已存在管理员
            $admin = Admin::where('username', 'admin')->first();
            if ($admin) {
                $this->warn('管理员账户已存在，跳过创建');
                return;
            }

            // 创建默认管理员
            $admin = new Admin();
            $admin->username = 'admin';
            $admin->password = password_hash('Yxsh0p@2024!Adm1n', PASSWORD_DEFAULT);
            $admin->nickname = '超级管理员';
            $admin->email = 'admin@yxshop.com';
            $admin->phone = '13800138000';
            $admin->status = 1;
            $admin->deleted_at = 0;
            $admin->app_id = 0;
            $admin->created_at = time();
            $admin->updated_at = time();
            $admin->save();

            $this->info('默认管理员账户创建成功！');
            $this->info('用户名: admin');
            $this->info('密码: Yxsh0p@2024!Adm1n');
            $this->warn('请登录后立即修改密码！');

            // 创建默认权限
            $this->createDefaultAuths();
            
            // 创建默认菜单
            $this->createDefaultMenus();

            $this->info('初始化完成！');

        } catch (\Exception $e) {
            $this->error('初始化失败: ' . $e->getMessage());
        }
    }

    /**
     * 创建默认权限
     */
    private function createDefaultAuths()
    {
        $auths = [
            ['name' => '系统管理', 'url' => 'admin', 'pid' => 0, 'sort' => 1],
            ['name' => '管理员管理', 'url' => 'admin/admin', 'pid' => 1, 'sort' => 1],
            ['name' => '权限管理', 'url' => 'admin/auth', 'pid' => 1, 'sort' => 2],
            ['name' => '菜单管理', 'url' => 'admin/menu', 'pid' => 1, 'sort' => 3],
            ['name' => '用户管理', 'url' => 'admin/user', 'pid' => 0, 'sort' => 2],
            ['name' => '商品管理', 'url' => 'admin/goods', 'pid' => 0, 'sort' => 3],
            ['name' => '订单管理', 'url' => 'admin/order', 'pid' => 0, 'sort' => 4],
            ['name' => '财务管理', 'url' => 'admin/finance', 'pid' => 0, 'sort' => 5],
            ['name' => '营销管理', 'url' => 'admin/marketing', 'pid' => 0, 'sort' => 6],
            ['name' => '系统设置', 'url' => 'admin/setting', 'pid' => 0, 'sort' => 7],
        ];

        foreach ($auths as $authData) {
            $auth = new Auth();
            $auth->name = $authData['name'];
            $auth->url = $authData['url'];
            $auth->pid = $authData['pid'];
            $auth->sort = $authData['sort'];
            $auth->deleted_at = 0;
            $auth->created_at = time();
            $auth->updated_at = time();
            $auth->save();
        }

        $this->info('默认权限创建成功');
    }

    /**
     * 创建默认菜单
     */
    private function createDefaultMenus()
    {
        $menus = [
            ['name' => '仪表盘', 'url' => 'admin/index', 'icon' => 'fas fa-tachometer-alt', 'pid' => 0, 'sort' => 1, 'model' => 'index'],
            ['name' => '用户管理', 'url' => 'admin/user', 'icon' => 'fas fa-users', 'pid' => 0, 'sort' => 2, 'model' => 'user'],
            ['name' => '商品管理', 'url' => 'admin/goods', 'icon' => 'fas fa-box', 'pid' => 0, 'sort' => 3, 'model' => 'goods'],
            ['name' => '订单管理', 'url' => 'admin/order', 'icon' => 'fas fa-shopping-cart', 'pid' => 0, 'sort' => 4, 'model' => 'order'],
            ['name' => '财务管理', 'url' => 'admin/finance', 'icon' => 'fas fa-money-bill', 'pid' => 0, 'sort' => 5, 'model' => 'finance'],
            ['name' => '营销管理', 'url' => 'admin/marketing', 'icon' => 'fas fa-bullhorn', 'pid' => 0, 'sort' => 6, 'model' => 'marketing'],
            ['name' => '系统设置', 'url' => 'admin/setting', 'icon' => 'fas fa-cog', 'pid' => 0, 'sort' => 7, 'model' => 'setting'],
        ];

        foreach ($menus as $menuData) {
            $menu = new Menu();
            $menu->name = $menuData['name'];
            $menu->url = $menuData['url'];
            $menu->icon = $menuData['icon'];
            $menu->pid = $menuData['pid'];
            $menu->sort = $menuData['sort'];
            $menu->model = $menuData['model'];
            $menu->deleted_at = 0;
            $menu->created_at = time();
            $menu->updated_at = time();
            $menu->save();
        }

        $this->info('默认菜单创建成功');
    }
}
