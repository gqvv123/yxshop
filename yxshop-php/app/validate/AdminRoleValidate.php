<?php

namespace app\validate;

use think\Validate;

/**
 * 管理员角色验证器
 */
class AdminRoleValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'role_name' => 'require|max:50',
        'role_desc' => 'max:200',
        'app_id' => 'require|number|min:1',
        'deleted_at' => 'in:0,1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'role_name.require' => '角色名称不能为空',
        'role_name.max' => '角色名称最多不能超过50个字符',
        'role_desc.max' => '角色描述最多不能超过200个字符',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
        'deleted_at.in' => '是否删除值必须是0或1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['role_name', 'role_desc', 'app_id', 'deleted_at'],
        'update' => ['role_name', 'role_desc', 'deleted_at'],
    ];
}