<?php

namespace app\validate;

use think\Validate;

/**
 * 用户验证器
 */
class UserValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'phone' => 'max:15|regex:/^1[3-9]\d{9}$/',
        'nickname' => 'max:255',
        'avatar_url' => 'max:255',
        'gender' => 'in:0,1,2',
        'country' => 'max:50',
        'province' => 'max:50',
        'city' => 'max:50',
        'password' => 'min:6|max:32',
        'app_id' => 'require|number|min:1',
        'integral' => 'number|min:0',
        'money' => 'number|min:0',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'phone.max' => '手机号最多不能超过15个字符',
        'phone.regex' => '手机号格式不正确',
        'nickname.max' => '昵称最多不能超过255个字符',
        'avatar_url.max' => '头像URL最多不能超过255个字符',
        'gender.in' => '性别值必须是0,1,2其中之一',
        'country.max' => '国家最多不能超过50个字符',
        'province.max' => '省份最多不能超过50个字符',
        'city.max' => '城市最多不能超过50个字符',
        'password.min' => '密码最少不能少于6个字符',
        'password.max' => '密码最多不能超过32个字符',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
        'integral.number' => '积分必须是数字',
        'integral.min' => '积分不能小于0',
        'money.number' => '余额必须是数字',
        'money.min' => '余额不能小于0',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['phone', 'nickname', 'avatar_url', 'gender', 'country', 'province', 'city', 'password', 'app_id', 'integral', 'money'],
        'update' => ['phone', 'nickname', 'avatar_url', 'gender', 'country', 'province', 'city', 'password', 'integral', 'money'],
        'register' => ['phone', 'password', 'app_id'],
        'login' => ['phone', 'password'],
    ];
}