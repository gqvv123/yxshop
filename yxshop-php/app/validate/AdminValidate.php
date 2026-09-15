<?php

namespace app\validate;

use think\Validate;

/**
 * 管理员验证器
 */
class AdminValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'username' => 'require|max:50',
        'password' => 'require|min:6|max:255',
        'nickname' => 'max:200',
        'phone' => 'max:11|regex:/^1[3-9]\d{9}$/',
        'email' => 'max:255|email',
        'app_id' => 'require|number|min:1',
        'is_super_admin' => 'in:0,1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'username.require' => '用户名不能为空',
        'username.max' => '用户名最多不能超过50个字符',
        'password.require' => '密码不能为空',
        'password.min' => '密码最少不能少于6个字符',
        'password.max' => '密码最多不能超过255个字符',
        'nickname.max' => '昵称最多不能超过200个字符',
        'phone.max' => '手机号最多不能超过11个字符',
        'phone.regex' => '手机号格式不正确',
        'email.max' => '邮箱最多不能超过255个字符',
        'email.email' => '邮箱格式不正确',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
        'is_super_admin.in' => '超级管理员值必须是0或1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['username', 'password', 'nickname', 'phone', 'email', 'app_id', 'is_super_admin'],
        'update' => ['username', 'nickname', 'phone', 'email', 'is_super_admin'],
        'login' => ['username', 'password'],
    ];
}