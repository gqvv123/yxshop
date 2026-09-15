<?php

namespace app\validate;

use think\Validate;

/**
 * 分销商申请验证器
 */
class ApplyValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'user_id' => 'require|number|min:1',
        'status' => 'require|in:10,20',
        'describe' => 'max:500',
        'app_id' => 'require|number|min:1',
        'phone' => 'require|max:15|regex:/^1[3-9]\d{9}$/',
        'referee_name' => 'max:255',
        'name' => 'require|max:255',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'user_id.require' => '分销商用户ID不能为空',
        'user_id.number' => '分销商用户ID必须是数字',
        'user_id.min' => '分销商用户ID不能小于1',
        'status.require' => '通过类型不能为空',
        'status.in' => '通过类型值必须是10或20',
        'describe.max' => '描述最多不能超过500个字符',
        'app_id.require' => '应用ID不能为空',
        'app_id.number' => '应用ID必须是数字',
        'app_id.min' => '应用ID不能小于1',
        'phone.require' => '手机号不能为空',
        'phone.max' => '手机号最多不能超过15个字符',
        'phone.regex' => '手机号格式不正确',
        'referee_name.max' => '推荐人名称最多不能超过255个字符',
        'name.require' => '姓名不能为空',
        'name.max' => '姓名最多不能超过255个字符',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['user_id', 'status', 'describe', 'app_id', 'phone', 'referee_name', 'name'],
        'update' => ['status', 'describe', 'phone', 'referee_name', 'name'],
    ];
}