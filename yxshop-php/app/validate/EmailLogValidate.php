<?php

namespace app\validate;

use think\Validate;

/**
 * 邮件日志验证器
 */
class EmailLogValidate extends Validate
{
    /**
     * 验证规则
     * @var array
     */
    protected $rule = [
        'user_id' => 'require|number|min:1',
        'email' => 'require|email|max:100',
        'email_type' => 'require|in:10,20,30,40,50',
        'email_subject' => 'require|max:200',
        'email_content' => 'require',
        'email_status' => 'require|in:10,20,30,40',
        'email_result' => 'max:200',
        'ip' => 'max:50',
        'app_id' => 'require|number|min:1',
    ];

    /**
     * 错误消息
     * @var array
     */
    protected $message = [
        'user_id.require' => '用户ID不能为空',
        'user_id.number' => '用户ID必须是数字',
        'user_id.min' => '用户ID不能小于1',
        'email.require' => '邮箱地址不能为空',
        'email.email' => '邮箱地址格式不正确',
        'email.max' => '邮箱地址最多不能超过100个字符',
        'email_type.require' => '邮件类型不能为空',
        'email_type.in' => '邮件类型值必须是10,20,30,40,50其中之一',
        'email_subject.require' => '邮件主题不能为空',
        'email_subject.max' => '邮件主题最多不能超过200个字符',
        'email_content.require' => '邮件内容不能为空',
        'email_status.require' => '发送状态不能为空',
        'email_status.in' => '发送状态值必须是10,20,30,40其中之一',
        'email_result.max' => '发送结果最多不能超过200个字符',
        'ip.max' => 'IP地址最多不能超过50个字符',
        'app_id.require' => '小程序ID不能为空',
        'app_id.number' => '小程序ID必须是数字',
        'app_id.min' => '小程序ID不能小于1',
    ];

    /**
     * 验证场景
     * @var array
     */
    protected $scene = [
        'create' => ['user_id', 'email', 'email_type', 'email_subject', 'email_content', 'email_status', 'email_result', 'ip', 'app_id'],
        'update' => ['email_result', 'email_status'],
    ];
}