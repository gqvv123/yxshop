<?php

namespace app\validate;

use think\Validate;

/**
 * 登录日志验证器
 */
class LoginLogValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'user_type' => 'require|max:10',
        'user_id' => 'require|number|min:1',
        'user_name' => 'require|max:100',
        'login_type' => 'require|in:10,20,30',
        'login_result' => 'require|in:10,20',
        'fail_reason' => 'max:200',
        'login_ip' => 'require|max:50',
        'user_agent' => 'max:500',
        'location' => 'max:100',
        'app_id' => 'require|number|min:1',
        'login_message' => 'max:200',
        'login_status' => 'max:50',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'user_type.require' => '用户类型不能为空',
        'user_type.max' => '用户类型最多不能超过10个字符',
        'user_id.require' => '用户ID不能为空',
        'user_id.number' => '用户ID必须是数字',
        'user_id.min' => '用户ID不能小于1',
        'user_name.require' => '用户名不能为空',
        'user_name.max' => '用户名最多不能超过100个字符',
        'login_type.require' => '登录类型不能为空',
        'login_type.in' => '登录类型值必须是10,20,30其中之一',
        'login_result.require' => '登录结果不能为空',
        'login_result.in' => '登录结果值必须是10或20',
        'fail_reason.max' => '失败原因最多不能超过200个字符',
        'login_ip.require' => 'IP地址不能为空',
        'login_ip.max' => 'IP地址最多不能超过50个字符',
        'user_agent.max' => '用户代理最多不能超过500个字符',
        'location.max' => '登录地点最多不能超过100个字符',
        'app_id.require' => '小程序ID不能为空',
        'app_id.number' => '小程序ID必须是数字',
        'app_id.min' => '小程序ID不能小于1',
        'login_message.max' => '登陆信息最多不能超过200个字符',
        'login_status.max' => '登陆状态最多不能超过50个字符',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['user_type', 'user_id', 'user_name', 'login_type', 'login_result', 'fail_reason', 'login_ip', 'user_agent', 'location', 'app_id', 'login_message', 'login_status'],
        'update' => ['login_result', 'fail_reason', 'login_message', 'login_status'],
    ];
}