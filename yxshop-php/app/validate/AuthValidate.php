<?php

namespace app\validate;

use think\Validate;

/**
 * 权限验证器
 */
class AuthValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'name' => 'require|max:50',
        'sort' => 'number|min:0',
        'deleted_at' => 'in:0,1',
        'user_id' => 'require|number|min:1',
        'url' => 'max:255',
        'pid' => 'number|min:0',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'name.require' => '权限名称不能为空',
        'name.max' => '权限名称最多不能超过50个字符',
        'sort.number' => '排序必须是数字',
        'sort.min' => '排序不能小于0',
        'deleted_at.in' => '是否删除值必须是0或1',
        'user_id.require' => '主用户ID不能为空',
        'user_id.number' => '主用户ID必须是数字',
        'user_id.min' => '主用户ID不能小于1',
        'url.max' => '连接最多不能超过255个字符',
        'pid.number' => '父级ID必须是数字',
        'pid.min' => '父级ID不能小于0',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['name', 'sort', 'deleted_at', 'user_id', 'url', 'pid'],
        'update' => ['name', 'sort', 'deleted_at', 'user_id', 'url', 'pid'],
    ];
}