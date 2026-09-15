<?php

namespace app\validate;

use think\Validate;

/**
 * 管理员权限验证器
 */
class AdminAuthValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'admin_id' => 'require|number|min:1',
        'auth_id' => 'require|number|min:1',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'admin_id.require' => '管理员ID不能为空',
        'admin_id.number' => '管理员ID必须是数字',
        'admin_id.min' => '管理员ID不能小于1',
        'auth_id.require' => '权限ID不能为空',
        'auth_id.number' => '权限ID必须是数字',
        'auth_id.min' => '权限ID不能小于1',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['admin_id', 'auth_id', 'app_id'],
        'update' => ['admin_id', 'auth_id'],
    ];
}